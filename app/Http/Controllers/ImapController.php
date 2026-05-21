<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Department;
use App\Models\Email;
use App\Models\EmailAttachment;
use App\Models\ImapAccount;
use App\Models\Project;
use App\Models\Task;
use App\Notifications\EmailReceivedNotification;
use App\Traits\MediaTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Webklex\IMAP\Facades\Client;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Webklex\PHPIMAP\Query\WhereQuery as query;

class ImapController extends Controller
{
    use MediaTrait;

    public function fetchEmails(Request $request)
    {
        return $this->fetchDepartmentMails($request);
        // ini_set('memory_limit', '528M');
        // config(['mail.imap.default.username' => 'dealreview@testsolencrm.com']);
        // config(['mail.imap.default.password' => 'Deal@247']);
        // config(['config.imap.accounts.default.username' => 'sitesurvey@testsolencrm.com']);
        // config(['config.imap.accounts.default.password' => 'Site@247']);
        $client = Client::account('default');
        $client->connect();
        if ($client->isConnected()) {
            $folders = $client->getFolders();
            foreach ($folders as $key => $folder) {
                // return $folder->search();
                $query = $folder->query();
                $messages = $query->from('hmadilkhan@gmail.com')->get();
                // $messages = $folder->messages()->all()->get();
                foreach ($messages as $key => $message) {
                    echo $message->message_id . '<br />';
                    echo $message->getSubject() . '<br />';
                    // echo $message->getHTMLBody() . '<br />';
                    echo $message->getTextBody() . '<br />';
                    echo 'Attachments: ' . $message->getAttachments()->count() . '<br />';
                    echo '<br />';
                    if ($message->getAttachments()->count() > 0) {
                        $attachments = $message->getAttachments();
                        foreach ($attachments as $attachment) {
                            // $attachment->save($path = public_path('/storage/emails/'), $filename = null);
                            // if (!empty($attachment)) {
                            //     echo $attachment->name;
                            // }
                        }
                    }
                }
            }
            // $messages = $query->from('example@domain.com')->get();
        } else {
            return 0;
        }
    }

    public function fetchDepartmentMails(Request $request)
    {
        $currentAccount = "unknown";

        try {
            $departments = Department::all();
            $customer = Customer::findOrFail($request->customer_id); //$request->customer_id
            $project = Project::where("id", $request->project_id)
                ->where("customer_id", $customer->id)
                ->firstOrFail();

            foreach ($departments as $key => $department) {
                $account = ImapAccount::where("department_id", $department->id)->first();
                if (!empty($account)) {
                    $currentAccount = $account->account;
                    $client = Client::account($account->account);
                    $client->connect();
                    if ($client->isConnected()) {
                        $folders = $client->getFolders();
                        foreach ($folders as $key => $folder) {
                            $query = $folder->query();
                            $messages = $query->from($customer->email)->get();
                            foreach ($messages as $key => $message) {
                                $messageId = $message->message_id ?: sha1($department->id . $request->project_id . $message->getDate() . $message->getSubject() . $message->getTextBody());
                                $email = Email::where("project_id", $request->project_id)
                                    ->where("department_id", $department->id)
                                    ->where("message_id", $messageId)
                                    ->first();

                                if (!$email) {
                                    $email = Email::create([
                                        "project_id" =>  $project->id, // $request->project_id,
                                        "department_id" => $department->id,
                                        "customer_id" => $request->customer_id,
                                        "subject" => $message->getSubject(),
                                        "body" => $message->getTextBody(),
                                        "message_id" => $messageId,
                                        "received_date" => $message->getDate(),
                                        "is_view" => 1,
                                    ]);
                                    $this->notifyAssignedEmployeeAboutEmail($project, $email, $customer->email);
                                } else {

                                    $email->update(["received_date" => $message->getDate(), "updated_at" => date("Y-m-d H:i:s")]);
                                }

                                if ($message->getAttachments()->count() > 0) {
                                    foreach ($message->getAttachments() as $attachment) {
                                        $this->storeEmailAttachment($email, $attachment);
                                    }
                                }
                            }
                        }
                    }
                    $client->disconnect();
                }
            }
            return response()->json(["status" => 200, "message" => "Email fetched completed."]);
        } catch (\Throwable $th) {
            return response()->json(["status" => 500, "message" => "Some Error Occurred.", "error" => $currentAccount . " - " . $th->getMessage()]);
        }
    }

    public function showEmails(Request $request)
    {
        $project = Project::with("emails", "emails.attachments", "emails.user")->where("id", $request->project_id)->first();

        return view("projects.partial.show-emails", [
            "project" => $project,
            "departments" => Department::all(),
        ]);
    }

    private function storeEmailAttachment(Email $email, $attachment): void
    {
        if (empty($attachment)) {
            return;
        }

        $originalName = $attachment->name ?: 'attachment';
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $name = pathinfo($originalName, PATHINFO_FILENAME);
        $safeName = Str::slug($name) ?: 'attachment';
        $storedName = $email->id . '-' . sha1($originalName . $email->message_id . $attachment->content) . '-' . $safeName;

        if ($extension) {
            $storedName .= '.' . strtolower($extension);
        }

        if (EmailAttachment::where("email_id", $email->id)->where("file", $storedName)->exists()) {
            return;
        }

        Storage::disk('public')->put('emails/' . $storedName, $attachment->content);
        EmailAttachment::create([
            "email_id" => $email->id,
            "file" => $storedName,
        ]);
    }

    private function notifyAssignedEmployeeAboutEmail(Project $project, Email $email, string $sender): void
    {
        $task = Task::with("employee.user")
            ->where("project_id", $project->id)
            ->whereIn("status", ["In-Progress", "Hold", "Cancelled"])
            ->latest("id")
            ->first();

        if (!$task?->employee?->user) {
            return;
        }

        Notification::send($task->employee->user, new EmailReceivedNotification($project, $email, $sender));
    }
}
