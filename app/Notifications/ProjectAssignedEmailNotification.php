<?php

namespace App\Notifications;

use App\Models\Project;
use App\Models\Task;
use App\Services\NotificationTemplateService;
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

        $mail = app(NotificationTemplateService::class)->render('project_assigned', [
            'recipient_name' => $notifiable->name,
            'project_name' => $projectName,
            'project_code' => $this->project->code,
            'customer_name' => $customerName,
            'department_name' => $departmentName,
            'assigned_by' => $this->assignedBy,
            'notes' => $this->task->assign_to_notes,
            'project_url' => route('projects.show', $this->project->id),
        ]);

        return (new MailMessage)
            ->subject($mail['subject'])
            ->markdown('emails.notification-template', ['body' => $mail['body']]);
    }
}
