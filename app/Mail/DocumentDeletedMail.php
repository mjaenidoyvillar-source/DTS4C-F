<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DocumentDeletedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $documentTitle;
    public $deleterName;

    public function __construct(string $documentTitle, ?string $deleterName = null)
    {
        $this->documentTitle = $documentTitle;
        $this->deleterName = $deleterName;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Document Deleted: ' . $this->documentTitle,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.document-deleted',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}

