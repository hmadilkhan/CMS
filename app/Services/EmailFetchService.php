<?php

namespace App\Services;

use App\Models\Department;
use App\Models\Customer;
use App\Models\Project;
use App\Models\ImapAccount;
use App\Models\Email;
use App\Models\EmailAttachment;
use App\Models\Task;
use App\Notifications\EmailReceivedNotification;
use Webklex\IMAP\Facades\Client;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
                                $messageId = $message->message_id ?: sha1($department->id . $project->id . $message->getDate() . $message->getSubject() . $message->getTextBody());
                                $email = Email::where('project_id', $project->id)
                                    ->where('department_id', $department->id)
                                    ->where('message_id', $messageId)
                                    ->first();

                                if (!$email) {
                                    $email = Email::create([
                                        'project_id' => $project->id,
                                        'department_id' => $department->id,
                                        'customer_id' => $customer->id,
                                        'subject' => $message->getSubject(),
                                        'body' => $message->getTextBody(),
                                        'message_id' => $messageId,
                                        'received_date' => $message->getDate(),
                                        'is_view' => 1,
                                    ]);
                                    $this->notifyAssignedEmployeeAboutEmail($project, $email, $customer->email);
                                } else {
                                    $email->update([
                                        'received_date' => $message->getDate(),
                                        'updated_at' => now(),
                                    ]);
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
        }
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

        if (EmailAttachment::where('email_id', $email->id)->where('file', $storedName)->exists()) {
            return;
        }

        Storage::disk('public')->put('emails/' . $storedName, $attachment->content);
        EmailAttachment::create([
            'email_id' => $email->id,
            'file' => $storedName,
        ]);
    }

    private function notifyAssignedEmployeeAboutEmail(Project $project, Email $email, string $sender): void
    {
        $task = Task::with('employee.user')
            ->where('project_id', $project->id)
            ->whereIn('status', ['In-Progress', 'Hold', 'Cancelled'])
            ->latest('id')
            ->first();

        if (!$task?->employee?->user) {
            return;
        }

        Notification::send($task->employee->user, new EmailReceivedNotification($project, $email, $sender));
    }
} 
