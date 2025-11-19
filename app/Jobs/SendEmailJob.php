<?php

namespace App\Jobs;

use App\Mail\TestEmail;
use App\Models\Email;
use App\Models\EmailAttachment;
use App\Models\EmailConfig;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $details;
    protected $uploadedFiles = [];
    protected $ccEmails = [];
    /**
     * Create a new job instance.
     */
    public function __construct($details, $files,$ccEmails = [])
    {
        $this->details = $details;
        $this->uploadedFiles = $files;
        $this->ccEmails = $ccEmails;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {

        // Here mailer function use for sending emails with different account. This account defines in mail.php and .env file.
        // Mail::mailer('info')->to($recipient)->send(new OrderShipped($order));

        // Mail::to("info@testsolencrm.com")->send(new TestEmail($this->details,$this->uploadedFiles));

        $email = Email::create([
            "project_id" => $this->details['project_id'],
            "department_id" => $this->details['department_id'],
            "customer_id" => $this->details['customer_id'],
            "subject" => $this->details['subject'],
            "body" => $this->details['body'],
            "user_id" => $this->details['user_id'],
        ]);
        foreach ($this->uploadedFiles as $key => $file) {
            EmailAttachment::create([
                "email_id" => $email->id,
                "file" => $file,
            ]);
        }
        // if ($this->details['department_id'] == 1) {
        //     Mail::to($this->details['customer_email'])->send(new TestEmail($this->details, $this->uploadedFiles));
        // }elseif ($this->details['department_id'] == 2) {
        //     Mail::mailer('info')->to($this->details['customer_email'])->send(new TestEmail($this->details, $this->uploadedFiles));
        // }

        $config = EmailConfig::where("department_id",$this->details['department_id'])->first();
        if(!empty($config)){
            Mail::mailer($config->mailer_name)->to($this->details['customer_email'])->send(new TestEmail($this->details, $this->uploadedFiles,$this->ccEmails));
        }

        // This needs to be run to process the queue and if we want to do this automatically then we need to do this by scheduling this commands on the server side.        
        //PHP artisan queue:listen
        // php artisan queue:restart
    }
}
