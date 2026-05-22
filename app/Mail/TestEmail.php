<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class TestEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $subject;
    public $body;
    public $uploadedFiles = [];
    public $sendAttachments = [];
    public $ccEmails = [];
    public $messageId;

    /**
     * Create a new message instance.
     */
    public function __construct($details, $files,$ccEmails = [])
    {
        $this->subject = $details['subject'];
        $this->body = $details['body'];
        $this->uploadedFiles = $files;
        $this->ccEmails = is_array($ccEmails) ? array_filter($ccEmails) : array_filter((array) $ccEmails);
        $this->messageId = $details['message_id'] ?? null;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subject,
            cc: $this->ccEmails,
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

    public function headers(): Headers
    {
        return new Headers(
            messageId: $this->messageId,
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        $attachments = [];

        foreach ($this->uploadedFiles as $file) {
            $path = Storage::disk('public')->path("emails/{$file}");

            if (is_file($path)) {
                $attachments[] = Attachment::fromPath($path)->as($file);
            }
        }

        return $attachments;
    }

}
