<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DocumentRejectedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $documentTitle;
    public $reason;
    public $rejectorName;

    public function __construct(string $documentTitle, ?string $reason = null, ?string $rejectorName = null)
    {
        $this->documentTitle = $documentTitle;
        $this->reason = $reason;
        $this->rejectorName = $rejectorName;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Document Rejected: ' . $this->documentTitle,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.document-rejected',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}

