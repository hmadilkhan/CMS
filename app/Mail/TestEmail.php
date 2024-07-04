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
        foreach ($this->uploadedFiles as $key => $file) {
        //    array_push($this->sendAttachments, Attachment::fromPath(public_path("/storage/emails/$file")));
           array_push($this->sendAttachments, Attachment::fromPath(asset("/storage/emails/".$file)));
        }
        return $this->sendAttachments;
    }
}
