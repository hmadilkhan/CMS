<?php

namespace App\Jobs;

use App\Mail\AcceptanceEmail;
use App\Models\EmailConfig;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class AcceptanceEmailJob implements ShouldQueue
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
        $config = EmailConfig::where("department_id",$this->details['department_id'])->first();
        if(!empty($config)){
            Mail::mailer($config->mailer_name)->to($this->details['customer_email'])->send(new AcceptanceEmail($this->details, $this->uploadedFiles,$this->ccEmails));
        }
    }
}
