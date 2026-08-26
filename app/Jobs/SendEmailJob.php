<?php

namespace App\Jobs;

use App\Mail\TestEmail;
use App\Models\Email;
use App\Models\EmailAttachment;
use App\Models\EmailConfig;
use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

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

        // Everything is checked BEFORE the send. The emails row is written
        // after the message goes out, so anything that can fail on that insert
        // has to be caught here — otherwise the job retries and the customer
        // receives the same email two or three more times.
        $this->details = $this->normaliseDetails($this->details);

        foreach (["project_id", "department_id", "customer_id", "customer_email", "body"] as $required) {
            if (!isset($this->details[$required])) {
                throw new \RuntimeException("Cannot send email: '{$required}' is missing from the email details.");
            }
        }

        $config = EmailConfig::where("department_id", $this->details['department_id'])->first();

        if (empty($config)) {
            throw new \RuntimeException("Email configuration is missing for this department.");
        }

        $this->details['message_id'] = $this->details['message_id'] ?? $this->makeMessageId();

        Mail::mailer($config->mailer_name)
            ->to($this->details['customer_email'])
            ->send(new TestEmail($this->details, $this->uploadedFiles, $this->ccEmails));

        // Sent. From here a failure must not bubble up, or the retry re-sends.
        try {
            $email = Email::create([
                "project_id" => $this->details['project_id'],
                "department_id" => $this->details['department_id'],
                "customer_id" => $this->details['customer_id'],
                "subject" => $this->details['subject'],
                "body" => $this->details['body'],
                "user_id" => $this->details['user_id'] ?? null,
                "message_id" => $this->details['message_id'],
                "direction" => "sent",
            ]);
            foreach ($this->uploadedFiles as $key => $file) {
                EmailAttachment::create([
                    "email_id" => $email->id,
                    "file" => $file,
                ]);
            }
        } catch (\Throwable $exception) {
            Log::error('Email was sent but could not be recorded in the CRM.', [
                'project_id' => $this->details['project_id'] ?? null,
                'department_id' => $this->details['department_id'] ?? null,
                'customer_email' => $this->details['customer_email'] ?? null,
                'message_id' => $this->details['message_id'] ?? null,
                'message' => $exception->getMessage(),
            ]);
        }

        // This needs to be run to process the queue and if we want to do this automatically then we need to do this by scheduling this commands on the server side.
        //PHP artisan queue:listen
        // php artisan queue:restart
    }

    /**
     * Trim the payload and drop blanks, so an empty string never reaches a
     * NOT NULL column. `subject` and `body` are NOT NULL on `emails`; a
     * missing subject falls back to the project code rather than blocking an
     * email the user already composed.
     */
    private function normaliseDetails(array $details): array
    {
        foreach ($details as $key => $value) {
            if (is_string($value)) {
                $value = trim($value);
                $details[$key] = $value === '' ? null : $value;
            }
        }

        $details = array_filter($details, fn ($value) => !is_null($value));

        if (!isset($details['subject'])) {
            $code = optional(Project::find($details['project_id'] ?? null))->code;
            $details['subject'] = $code ? "Project Update [{$code}]" : 'Project Update';
        }

        return $details;
    }

    private function makeMessageId(): string
    {
        $host = parse_url(config('app.url'), PHP_URL_HOST) ?: 'solaroperations.info';

        return 'crm-email-' . Str::uuid() . '@' . $host;
    }
}
