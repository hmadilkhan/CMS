<?php

namespace App\Notifications;

use App\Services\NotificationTemplateService;
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
        $mail = app(NotificationTemplateService::class)->render('service_ticket_created', [
            'recipient_name' => $notifiable->name,
            'ticket_id' => $this->ticket->id,
            'ticket_subject' => $this->ticket->subject,
            'priority' => $this->ticket->priority,
            'project_name' => optional($this->ticket->project)->project_name,
            'notes' => $this->ticket->notes ?? 'N/A',
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
            'priority' => $this->ticket->priority,
            'project_id' => $this->ticket->project_id,
        ];
    }
}
