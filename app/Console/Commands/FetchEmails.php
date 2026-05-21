<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\Department;
use App\Models\Email;
use App\Models\EmailAttachment;
use App\Models\ImapAccount;
use App\Models\Project;
use App\Models\Task;
use Webklex\IMAP\Facades\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Notification;
use App\Notifications\EmailReceivedNotification;
use Illuminate\Support\Str;

class FetchEmails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fetch-emails';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $projects = Project::with("customer")->where("department_id", "!=", 9)->get();
        foreach ($projects as $key => $project) {
            $account = ImapAccount::where("department_id", $project->department_id)->first();
            if (!empty($account)) {
                $client = Client::account($account->account);
                $client->connect();
                if ($client->isConnected()) {
                    $folders = $client->getFolders();
                    foreach ($folders as $key => $folder) {
                        $query = $folder->query();
                        $messages = $query->from($project->customer->email)->get();
                        foreach ($messages as $key => $message) {
                            $messageId = $message->message_id ?: sha1($project->department_id . $project->id . $message->getDate() . $message->getSubject() . $message->getTextBody());
                            $email = Email::where("project_id", $project->id)
                                ->where("department_id", $project->department_id)
                                ->where("message_id", $messageId)
                                ->first();

                            if (!$email) {
                                $email = Email::create([
                                    "project_id" =>  $project->id, // $request->project_id,
                                    "department_id" => $project->department_id,
                                    "customer_id" => $project->customer_id,
                                    "subject" => $message->getSubject(),
                                    "body" => $message->getTextBody(),
                                    "message_id" => $messageId,
                                    "received_date" => $message->getDate(),
                                    "is_view" => 1,
                                ]);
                                $this->notifyAssignedEmployeeAboutEmail($project, $email, $project->customer->email);
                                // $user = $project ? $project->user : null; // Adjust as needed

                                // if ($project && $user) {
                                //     Notification::send($user, new EmailReceivedNotification($project, $email, $email->from ?? null));
                                // }

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
        $this->info('All emails fetched successfully.');
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
