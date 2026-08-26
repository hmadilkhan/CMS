<?php

namespace App\Notifications;

use App\Services\NotificationTemplateService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class NoteMentionedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $project;
    public $note;
    public $mentionedBy;

    /**
     * Create a new notification instance.
     */
    public function __construct($project, $note, $mentionedBy)
    {
        $this->project = $project;
        $this->note = $note;
        $this->mentionedBy = $mentionedBy;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $mail = app(NotificationTemplateService::class)->render('note_mentioned', [
            'recipient_name' => $notifiable->name,
            'mentioned_by' => $this->mentionedBy->name,
            'project_name' => $this->project->project_name,
            'note' => $this->note,
            'project_url' => url('/projects/' . $this->project->id),
        ]);

        return (new MailMessage)
            ->subject($mail['subject'])
            ->markdown('emails.notification-template', ['body' => $mail['body']]);
    }

    /**
     * Get the array representation of the notification (for database).
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $data = [
            'project_id' => $this->project->id,
            'project_name' => $this->project->project_name,
            'note' => $this->note,
            'mentioned_by' => $this->mentionedBy->name,
            'message' => $this->mentionedBy->name . ' mentioned you in a note on project: ' . $this->project->project_name,
            'url' => url('/projects/' . $this->project->id),
        ];
        // Log::info('NoteMentionedNotification toArray', $data);
        return $data;
    }
}
