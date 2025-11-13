<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DocumentReceivedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $documentTitle;
    public $receiverName;

    public function __construct(string $documentTitle, ?string $receiverName = null)
    {
        $this->documentTitle = $documentTitle;
        $this->receiverName = $receiverName;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Document Received: ' . $this->documentTitle,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.document-received',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}

