<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class GenericQueueMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $subjectLine;

    public $bodyContent;

    /**
     * Create a new message instance.
     */
    public function __construct($subject, $body)
    {
        $this->subjectLine = $subject;
        $this->bodyContent = $body;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subjectLine,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            text: 'emails.generic_raw',
        );
    }
}
