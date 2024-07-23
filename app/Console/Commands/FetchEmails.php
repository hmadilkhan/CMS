<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\Department;
use App\Models\Email;
use App\Models\EmailAttachment;
use App\Models\ImapAccount;
use App\Models\Project;
use Webklex\IMAP\Facades\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

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
        $projects = Project::with("customer")->whereN("department_id", "!=", 9)->get();
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
                            $count = Email::where("message_id", $message->message_id)->count();
                            if ($count == 0) {
                                $email = Email::create([
                                    "project_id" =>  $project->id, // $request->project_id,
                                    "department_id" => $project->department_id,
                                    "customer_id" => $project->customer_id,
                                    "subject" => $message->getSubject(),
                                    "body" => $message->getTextBody(),
                                    "message_id" => $message->message_id,
                                    "received_date" => $message->getDate(),
                                    "is_view" => 1,
                                ]);
                                if ($message->getAttachments()->count() > 0) {
                                    $attachments = $message->getAttachments();
                                    foreach ($attachments as $attachment) {
                                        $filePath = 'public/emails/' . $attachment->name;
                                        Storage::put($filePath, $attachment->content);
                                        if (!empty($attachment)) {
                                            EmailAttachment::create([
                                                "email_id" => $email->id,
                                                "file" => $attachment->name,
                                            ]);
                                        }
                                    }
                                }
                            } else {

                                $email = Email::where("message_id", $message->message_id)->first();
                                Email::where("message_id", $message->message_id)->update(["received_date" => $message->getDate(), "updated_at" => date("Y-m-d H:i:s")]);

                                if ($message->getAttachments()->count() > 0) {
                                    $attachments = $message->getAttachments();
                                    foreach ($attachments as $attachment) {
                                        $attachmentCount = EmailAttachment::where("email_id", $email->id)->where("file", $attachment->name)->count();
                                        if ($attachmentCount == 0) {
                                            $filePath = 'public/emails/' . $attachment->name;
                                            Storage::put($filePath, $attachment->content);
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
    }
}
