<?php

namespace App\Notifications;

use App\Services\NotificationTemplateService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\ServiceTicket;
use App\Models\ServiceTicketComment;

class ServiceTicketCommentAdded extends Notification implements ShouldQueue
{
    use Queueable;

    public $ticket;
    public $comment;

    public function __construct(ServiceTicket $ticket, ServiceTicketComment $comment)
    {
        $this->ticket = $ticket;
        $this->comment = $comment;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        $mail = app(NotificationTemplateService::class)->render('service_ticket_comment', [
            'recipient_name' => $notifiable->name,
            'ticket_id' => $this->ticket->id,
            'ticket_subject' => $this->ticket->subject,
            'comment_by' => optional($this->comment->user)->name,
            'comment' => $this->comment->comment,
            'ticket_url' => route('service.dashboard'),
        ]);

        return (new MailMessage)
            ->subject($mail['subject'])
            ->markdown('emails.notification-template', ['body' => $mail['body']]);
    }

    public function toArray($notifiable)
    {
        return [
            'ticket_id' => $this->ticket->id,
            'subject' => $this->ticket->subject,
            'comment_id' => $this->comment->id,
            'comment' => $this->comment->comment,
            'commenter' => $this->comment->user->name,
        ];
    }
}
