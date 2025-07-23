<?php

namespace App\Services;

use App\Models\Department;
use App\Models\Customer;
use App\Models\Project;
use App\Models\ImapAccount;
use App\Models\Email;
use App\Models\EmailAttachment;
use Webklex\IMAP\Facades\Client;
use Illuminate\Support\Facades\Storage;

class EmailFetchService
{
    public function fetchAll()
    {
        $departments = Department::all();
        $projects = Project::with('customer')->get();
        foreach ($projects as $project) {
            $customer = $project->customer;
            if (!$customer) continue;
            foreach ($departments as $department) {
                $account = ImapAccount::where('department_id', $department->id)->first();
                if (!empty($account)) {
                    $client = Client::account($account->account);
                    $client->connect();
                    if ($client->isConnected()) {
                        $folders = $client->getFolders();
                        foreach ($folders as $folder) {
                            $query = $folder->query();
                            $messages = $query->from($customer->email)->get();
                            foreach ($messages as $message) {
                                $count = Email::where('message_id', $message->message_id)->count();
                                if ($count == 0) {
                                    $email = Email::create([
                                        'project_id' => $project->id,
                                        'department_id' => $department->id,
                                        'customer_id' => $customer->id,
                                        'subject' => $message->getSubject(),
                                        'body' => $message->getTextBody(),
                                        'message_id' => $message->message_id,
                                        'received_date' => $message->getDate(),
                                        'is_view' => 1,
                                    ]);
                                    if ($message->getAttachments()->count() > 0) {
                                        $attachments = $message->getAttachments();
                                        foreach ($attachments as $attachment) {
                                            $filePath = 'public/emails/' . $attachment->name;
                                            Storage::put($filePath, $attachment->content);
                                            if (!empty($attachment)) {
                                                EmailAttachment::create([
                                                    'email_id' => $email->id,
                                                    'file' => $attachment->name,
                                                ]);
                                            }
                                        }
                                    }
                                } else {
                                    $email = Email::where('message_id', $message->message_id)->first();
                                    Email::where('message_id', $message->message_id)->update([
                                        'received_date' => $message->getDate(),
                                        'updated_at' => now(),
                                    ]);
                                    if ($message->getAttachments()->count() > 0) {
                                        $attachments = $message->getAttachments();
                                        foreach ($attachments as $attachment) {
                                            $attachmentCount = EmailAttachment::where('email_id', $email->id)->where('file', $attachment->name)->count();
                                            if ($attachmentCount == 0) {
                                                $filePath = 'public/emails/' . $attachment->name;
                                                Storage::put($filePath, $attachment->content);
                                                EmailAttachment::create([
                                                    'email_id' => $email->id,
                                                    'file' => $attachment->name,
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
} 