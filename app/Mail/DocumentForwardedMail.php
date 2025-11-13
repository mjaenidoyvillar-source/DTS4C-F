<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DocumentForwardedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $documentTitle;
    public $senderName;

    public function __construct(string $documentTitle, ?string $senderName = null)
    {
        $this->documentTitle = $documentTitle;
        $this->senderName = $senderName;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Document Forwarded to You: ' . $this->documentTitle,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.document-forwarded',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}

