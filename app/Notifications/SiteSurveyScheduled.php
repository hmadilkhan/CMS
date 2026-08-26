<?php

namespace App\Notifications;

use App\Services\NotificationTemplateService;
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
        $mail = app(NotificationTemplateService::class)->render('site_survey_scheduled', [
            'recipient_name' => $notifiable->name,
            'survey_date' => optional($this->survey->survey_date)->format('M d, Y'),
            'start_time' => $this->survey->start_time,
            'end_time' => $this->survey->end_time,
            'customer_address' => $this->survey->customer_address,
            'survey_url' => url('/site-surveys/' . $this->survey->id),
        ]);

        return (new MailMessage)
            ->subject($mail['subject'])
            ->markdown('emails.notification-template', ['body' => $mail['body']]);
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
