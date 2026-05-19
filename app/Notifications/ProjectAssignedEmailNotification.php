<?php

namespace App\Notifications;

use App\Models\Project;
use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProjectAssignedEmailNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Project $project,
        public Task $task,
        public string $assignedBy
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $projectName = str_replace('-', ' ', $this->project->project_name);
        $customer = $this->project->customer;
        $customerName = trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? ''));
        $departmentName = optional($this->task->department)->name
            ?? optional($this->project->department)->name
            ?? 'N/A';

        return (new MailMessage)
            ->subject('Project Assigned: ' . $projectName)
            ->markdown('emails.project-assigned', [
                'userName' => $notifiable->name,
                'projectName' => $projectName,
                'customerName' => $customerName,
                'departmentName' => $departmentName,
                'assignedBy' => $this->assignedBy,
                'notes' => $this->task->assign_to_notes,
                'projectUrl' => route('projects.show', $this->project->id),
                'logoUrl' => asset('assets/images/logo.png'),
                'companyName' => 'Solen Energy Co.',
            ]);
    }
}
