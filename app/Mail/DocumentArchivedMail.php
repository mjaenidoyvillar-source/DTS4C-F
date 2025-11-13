<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DocumentArchivedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $documentTitle;
    public $archiverName;

    public function __construct(string $documentTitle, ?string $archiverName = null)
    {
        $this->documentTitle = $documentTitle;
        $this->archiverName = $archiverName;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Document Archived: ' . $this->documentTitle,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.document-archived',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}

