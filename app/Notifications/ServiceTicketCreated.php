<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\ServiceTicket;

class ServiceTicketCreated extends Notification implements \Illuminate\Contracts\Queue\ShouldQueue
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
            ->subject('New Service Ticket Assigned - #' . $this->ticket->id)
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('A new service ticket has been assigned to you.')
            ->line('**Subject:** ' . $this->ticket->subject)
            ->line('**Priority:** ' . $this->ticket->priority)
            ->line('**Project:** ' . $this->ticket->project->project_name)
            ->line('**Notes:** ' . ($this->ticket->notes ?? 'N/A'))
            ->action('View Ticket', url('/service-tickets/' . $this->ticket->id . '/details'))
            ->line('Please review and take necessary action.');
    }

    public function toArray($notifiable)
    {
        return [
            'ticket_id' => $this->ticket->id,
            'subject' => $this->ticket->subject,
            'priority' => $this->ticket->priority,
            'project_id' => $this->ticket->project_id,
        ];
    }
}
