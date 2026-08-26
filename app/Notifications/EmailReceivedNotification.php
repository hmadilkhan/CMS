<?php

namespace App\Notifications;

use App\Services\NotificationTemplateService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmailReceivedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $project;
    public $email;
    public $sender;

    /**
     * Create a new notification instance.
     */
    public function __construct($project, $email, $sender = null)
    {
        $this->project = $project;
        $this->email = $email;
        $this->sender = $sender;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable)
    {
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable)
    {
        $mail = app(NotificationTemplateService::class)->render('email_received', [
            'recipient_name' => $notifiable->name,
            'project_name' => $this->project->project_name,
            'email_subject' => $this->email->subject,
            'sender' => $this->sender ?? 'Unknown',
            'project_url' => url('/projects/' . $this->project->id),
        ]);

        return (new MailMessage)
            ->subject($mail['subject'])
            ->markdown('emails.notification-template', ['body' => $mail['body']]);
    }

    /**
     * Get the array representation of the notification (for database).
     */
    public function toArray($notifiable)
    {
        return [
            'project_id' => $this->project->id,
            'project_name' => $this->project->project_name,
            'email_subject' => $this->email->subject,
            'email_from' => $this->sender ?? 'Unknown',
            'message' => 'A new email was received for project: ' . $this->project->project_name,
            'url' => url('/projects/' . $this->project->id),
        ];
    }
} 
