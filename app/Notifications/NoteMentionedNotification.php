<?php

namespace App\Notifications;

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
        return (new MailMessage)
            ->subject('You were mentioned in a project note')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line($this->mentionedBy->name . ' mentioned you in a note on project: ' . $this->project->project_name)
            ->line('Note: ' . $this->note)
            ->action('View Project', url('/projects/' . $this->project->id))
            ->line('Thank you for using our application!');
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
        Log::info('NoteMentionedNotification toArray', $data);
        return $data;
    }
}
