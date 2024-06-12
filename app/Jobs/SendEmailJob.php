<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $details;
    /**
     * Create a new job instance.
     */
    public function __construct($details)
    {
        $this->details = $details;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Here mailer function use for sending emails with different account. This account defines in mail.php and .env file.
        // Mail::mailer('info')->to($recipient)->send(new OrderShipped($order));

        $data = $this->details;

        Mail::send(['html' => 'demo_email_template'], $data, function ($message) use ($data) {

            $message->to('receiver@gmail.com', 'John')

                ->subject("This is test Queue.");

            $message->from('info@demo.com', 'LaravelQueue');
        });
        
        // This needs to be run to process the queue and if we want to do this automatically then we need to do this by scheduling this commands on the server side.        
        //PHP artisan queue:listen
    }
}
