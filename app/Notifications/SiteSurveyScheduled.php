<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class SiteSurveyScheduled extends Notification
{
    use Queueable;

    public $survey;

    public function __construct($survey)
    {
        $this->survey = $survey;
    }

    public function via($notifiable)
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('New Site Survey Scheduled')
            ->line('A new site survey has been scheduled for you.')
            ->line('Date: ' . $this->survey->survey_date->format('M d, Y'))
            ->line('Time: ' . $this->survey->start_time . ' - ' . $this->survey->end_time)
            ->line('Address: ' . $this->survey->customer_address)
            ->action('View Details', url('/site-surveys/' . $this->survey->id));
    }

    public function toArray($notifiable)
    {
        return [
            'survey_id' => $this->survey->id,
            'project_id' => $this->survey->project_id,
            'survey_date' => $this->survey->survey_date,
            'start_time' => $this->survey->start_time,
            'customer_address' => $this->survey->customer_address,
            'message' => 'New site survey scheduled for ' . $this->survey->survey_date->format('M d, Y')
        ];
    }
}
