<?php

namespace App\Notifications;

use App\Services\NotificationTemplateService;
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
        $mail = app(NotificationTemplateService::class)->render('service_ticket_resolved', [
            'recipient_name' => $notifiable->name,
            'ticket_id' => $this->ticket->id,
            'ticket_subject' => $this->ticket->subject,
            'project_name' => optional($this->ticket->project)->project_name,
            'priority' => $this->ticket->priority,
            'resolved_by' => optional($this->ticket->assignedUser)->name,
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
            'project_name' => $this->ticket->project->project_name,
            'resolved_by' => $this->ticket->assignedUser->name,
        ];
    }
}
