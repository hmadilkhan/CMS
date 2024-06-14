<?php

namespace App\Jobs;

use App\Mail\TestEmail;
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
    /**
     * Create a new job instance.
     */
    public function __construct($details,$files)
    {
        $this->details = $details;
        $this->uploadedFiles = $files;
        // dd(storage_path('app/public/emails/1718342100-beverages.png'));
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Here mailer function use for sending emails with different account. This account defines in mail.php and .env file.
        // Mail::mailer('info')->to($recipient)->send(new OrderShipped($order));
        // Mail::to("hmadilkhan@gmail.com")->send(new TestEmail($this->details,$this->uploadedFiles));
        // Mail::to("dealreview@testsolencrm.com")->send(new TestEmail($this->details,$this->uploadedFiles));

        // Mail::mailer('info')->to("dealreview@testsolencrm.com")->send(new TestEmail($this->details,$this->uploadedFiles));
        $data = $this->details;
        // $data = [];

        Mail::send([], $data, function ($message) use ($data) {

            $message->to('info@testsolencrm.com', 'Muhammad Adil Khan')
                ->subject($data['subject'])
                ->setBody('<h1>Hi, welcome user!</h1>', 'text/html'); // assuming text/plain;
            $message->attach(public_path('/uploads/tritech.png'));

            // $message->from('info@demo.com', 'LaravelQueue');
        });
        
        // This needs to be run to process the queue and if we want to do this automatically then we need to do this by scheduling this commands on the server side.        
        //PHP artisan queue:listen
    }
}
