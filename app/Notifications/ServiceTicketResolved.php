<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\ServiceTicket;

class ServiceTicketResolved extends Notification implements ShouldQueue
{
    use Queueable;

    public $ticket;

    public function __construct(ServiceTicket $ticket)
    {
        $this->ticket = $ticket;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Ticket Resolved - #' . $this->ticket->id)
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('Great news! Your service ticket has been resolved.')
            ->line('**Ticket Subject:** ' . $this->ticket->subject)
            ->line('**Project:** ' . $this->ticket->project->project_name)
            ->line('**Priority:** ' . $this->ticket->priority)
            ->line('**Resolved by:** ' . $this->ticket->assignedUser->name)
            ->action('View Ticket', route('service.dashboard'))
            ->line('Thank you for using our service!');
    }

    public function toArray($notifiable)
    {
        return [
            'ticket_id' => $this->ticket->id,
            'subject' => $this->ticket->subject,
            'priority' => $this->ticket->priority,
            'project_name' => $this->ticket->project->project_name,
            'resolved_by' => $this->ticket->assignedUser->name,
        ];
    }
}
