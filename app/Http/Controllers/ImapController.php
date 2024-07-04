<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Email;
use App\Models\EmailAttachment;
use App\Models\ImapAccount;
use App\Models\Project;
use App\Traits\MediaTrait;
use Illuminate\Http\Request;
use Webklex\IMAP\Facades\Client;
use Webklex\PHPIMAP\Query\WhereQuery as query;

class ImapController extends Controller
{
    use MediaTrait;

    public function fetchEmails(Request $request)
    {
        return $this->fetchDepartmentMails($request);
        ini_set('memory_limit', '528M');
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
        try {
            $departments = Department::all();
            foreach ($departments as $key => $department) {
                $account = ImapAccount::where("department_id", $department->id)->first();
                if (!empty($account)) {
                    $client = Client::account($account->account);
                    $client->connect();
                    if ($client->isConnected()) {
                        $folders = $client->getFolders();
                        foreach ($folders as $key => $folder) {
                            $query = $folder->query();
                            $messages = $query->from('hmadilkhan@gmail.com')->get();
                            foreach ($messages as $key => $message) {
                                $count = Email::where("message_id", $message->message_id)->count();
                                if ($count == 0) {
                                    # code...
                                    $email = Email::create([
                                        "project_id" => $request->project_id,
                                        "department_id" => $department->id,
                                        "customer_id" => $request->customer_id,
                                        "subject" => $message->getSubject(),
                                        "body" => $message->getHTMLBody(),
                                        "message_id" => $message->message_id,
                                    ]);
                                    if ($message->getAttachments()->count() > 0) {
                                        $attachments = $message->getAttachments();
                                        foreach ($attachments as $attachment) {
                                            $attachment->save($path = public_path('/storage/emails/'), $filename = null);
                                            if (!empty($attachment)) {
                                                echo $attachment->name;
                                                EmailAttachment::create([
                                                    "email_id" => $email->id,
                                                    "file" => $attachment->name,
                                                ]);
                                            }
                                        }
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
            return response()->json(["status" => 500, "message" => "Some Error Occurred.", "error" => $th->getMessage()]);
        }
    }

    public function showEmails(Request $request)
    {
        $project = Project::with("emails","emails.attachments")->where("id",$request->project_id)->first();

        return view("projects.partial.show-emails",[
            "project" => $project,
            "departments" => Department::all(),
        ]);
    }
}
