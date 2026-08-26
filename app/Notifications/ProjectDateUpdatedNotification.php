<?php

namespace App\Notifications;

use App\Models\Project;
use App\Services\NotificationTemplateService;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProjectDateUpdatedNotification extends Notification
{
    use Queueable;

    /**
     * @param  array<int, array{field: string, label: string, old: ?string, new: ?string}>  $changes
     */
    public function __construct(
        public Project $project,
        public array $changes,
        public string $updatedBy
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

        $mail = app(NotificationTemplateService::class)->render('project_date_updated', [
            'recipient_name' => $notifiable->name,
            'project_name' => $projectName,
            'project_code' => $this->project->code,
            'customer_name' => trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? '')),
            'milestone_headline' => $this->headline(),
            'milestone_details' => $this->milestoneDetails(),
            'updated_by' => $this->updatedBy,
            'updated_at' => now()->format('j F Y'),
            'project_url' => route('projects.show', $this->project->id),
        ]);

        return (new MailMessage)
            ->subject($mail['subject'])
            ->markdown('emails.notification-template', ['body' => $mail['body']]);
    }

    /**
     * A single milestone leads with what happened ("PTO Approved"); a batch of
     * them falls back to a neutral summary line.
     */
    protected function headline(): string
    {
        return count($this->changes) === 1
            ? $this->changes[0]['headline']
            : 'Project update';
    }

    /**
     * The changed dates as HTML, one headline plus sentence each. Kept out of
     * the editable template because the number of changes varies per email.
     */
    protected function milestoneDetails(): string
    {
        $html = '';

        foreach ($this->changes as $change) {
            $html .= '<p><strong>' . e($change['headline']) . '</strong><br>'
                . e($change['message']) . '</p>';
        }

        return $html;
    }
}
