<?php

namespace App\Notifications;

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
        return (new MailMessage)
            ->subject('New Comment on Ticket #' . $this->ticket->id)
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('A new comment has been added to your ticket.')
            ->line('**Ticket Subject:** ' . $this->ticket->subject)
            ->line('**Comment by:** ' . $this->comment->user->name)
            ->line('**Comment:** ' . $this->comment->comment)
            ->action('View Ticket', route('service.dashboard'))
            ->line('Thank you for using our service!');
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
