<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TestEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $subject;
    public $body;
    public $uploadedFiles = [];
    public $sendAttachments = [];

    /**
     * Create a new message instance.
     */
    public function __construct($details, $files)
    {
        $this->subject = $details['subject'];
        $this->body = $details['body'];
        $this->uploadedFiles = $files;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'mail.test-email',
            with: ["body" => $this->body],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        // foreach ($this->uploadedFiles as $key => $file) {
        //    array_push($this->sendAttachments, storage_path()."/app/public/emails/".$file);
        // }
        // return [$this->sendAttachments];
        return [
            // storage_path()."/app/public/emails/1718342100-beverages.png",
            // Attachment::fromStorage(storage_path()."/app/public/emails/1718342100-beverages.png")
            Attachment::fromPath(public_path('/uploads/tritech.png'))

        ];
    }
}
