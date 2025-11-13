<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\Document;
use App\Models\User;
use App\Models\DocumentRoute;
use Illuminate\Support\Facades\Mail;
use App\Mail\DocumentAssignedMail;
use App\Mail\DocumentSentMail;
use App\Mail\DocumentReceivedMail;
use App\Mail\DocumentForwardedMail;
use App\Mail\DocumentRejectedMail;
use App\Mail\DocumentArchivedMail;
use App\Mail\DocumentDeletedMail;

class NotificationService
{
    /**
     * Create a notification for a user
     * 
     * @param int $userId
     * @param int|null $documentId
     * @param string $message
     * @return Notification
     */
    public function create(int $userId, ?int $documentId, string $message): Notification
    {
        return Notification::create([
            'user_id' => $userId,
            'document_id' => $documentId,
            'message' => $message,
            'is_read' => false,
        ]);
    }

    /**
     * Notify sender handler when document is uploaded
     * 
     * @param Document $document
     * @return void
     */
    public function notifySenderHandler(Document $document): void
    {
        if ($document->current_handler_id) {
            $handler = User::find($document->current_handler_id);
            if ($handler) {
                $this->create(
                    $handler->id,
                    $document->id,
                    "New document '{$document->title}' uploaded and assigned to you for review."
                );
                
                // Send email notification
                try {
                    if ($handler->email) {
                        // Load owner relationship if not loaded
                        if (!$document->relationLoaded('owner')) {
                            $document->load('owner');
                        }
                        $senderName = $document->owner ? $document->owner->name : null;
                        \Log::info('Sending email to handler', [
                            'handler_email' => $handler->email,
                            'document_title' => $document->title,
                            'handler_id' => $handler->id
                        ]);
                        Mail::to($handler->email)->send(new DocumentAssignedMail($document->title, $senderName));
                        \Log::info('Email sent successfully to handler', ['email' => $handler->email]);
                    } else {
                        \Log::warning('Handler has no email address', ['handler_id' => $handler->id]);
                    }
                } catch (\Exception $e) {
                    \Log::error('Failed to send document assigned email: ' . $e->getMessage(), [
                        'handler_email' => $handler->email ?? 'N/A',
                        'document_id' => $document->id,
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            } else {
                \Log::warning('Handler not found', ['handler_id' => $document->current_handler_id]);
            }
        }
    }

    /**
     * Notify recipient handler when document is sent
     * 
     * @param Document $document
     * @param int $recipientHandlerId
     * @return void
     */
    public function notifyRecipientHandler(Document $document, int $recipientHandlerId): void
    {
        $this->create(
            $recipientHandlerId,
            $document->id,
            "Document '{$document->title}' has been sent to your department for processing."
        );
        
        // Send email notification
        try {
            $handler = User::find($recipientHandlerId);
            if ($handler && $handler->email) {
                // Load currentHandler relationship if not loaded
                if (!$document->relationLoaded('currentHandler')) {
                    $document->load('currentHandler');
                }
                $senderName = $document->currentHandler ? $document->currentHandler->name : null;
                \Log::info('Sending email to recipient handler', [
                    'handler_email' => $handler->email,
                    'document_title' => $document->title
                ]);
                Mail::to($handler->email)->send(new DocumentSentMail($document->title, $senderName));
                \Log::info('Email sent successfully to recipient handler', ['email' => $handler->email]);
            } else {
                \Log::warning('Recipient handler not found or has no email', [
                    'handler_id' => $recipientHandlerId,
                    'has_email' => $handler ? ($handler->email ? 'yes' : 'no') : 'user_not_found'
                ]);
            }
        } catch (\Exception $e) {
            \Log::error('Failed to send document sent email: ' . $e->getMessage(), [
                'handler_id' => $recipientHandlerId,
                'document_id' => $document->id,
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Notify recipient owner when document is forwarded
     * 
     * @param Document $document
     * @param int $recipientOwnerId
     * @return void
     */
    public function notifyRecipientOwner(Document $document, int $recipientOwnerId): void
    {
        $this->create(
            $recipientOwnerId,
            $document->id,
            "Document '{$document->title}' has been forwarded to you for review."
        );
        
        // Send email notification
        try {
            $owner = User::find($recipientOwnerId);
            if ($owner && $owner->email) {
                // Load currentHandler relationship if not loaded
                if (!$document->relationLoaded('currentHandler')) {
                    $document->load('currentHandler');
                }
                $senderName = $document->currentHandler ? $document->currentHandler->name : null;
                \Log::info('Sending email to recipient owner', [
                    'owner_email' => $owner->email,
                    'document_title' => $document->title
                ]);
                Mail::to($owner->email)->send(new DocumentForwardedMail($document->title, $senderName));
                \Log::info('Email sent successfully to recipient owner', ['email' => $owner->email]);
            } else {
                \Log::warning('Recipient owner not found or has no email', [
                    'owner_id' => $recipientOwnerId,
                    'has_email' => $owner ? ($owner->email ? 'yes' : 'no') : 'user_not_found'
                ]);
            }
        } catch (\Exception $e) {
            \Log::error('Failed to send document forwarded email: ' . $e->getMessage(), [
                'owner_id' => $recipientOwnerId,
                'document_id' => $document->id,
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Notify uploader when document is rejected
     * 
     * @param Document $document
     * @param string $reason
     * @param User|null $rejector The user who rejected the document
     * @return void
     */
    public function notifyRejection(Document $document, string $reason = '', ?User $rejector = null): void
    {
        $rejectorName = $rejector ? $rejector->name : null;

        // Notify the original owner
        if ($document->owner_id) {
            $message = "Document '{$document->title}' has been rejected.";
            if ($reason) {
                $message .= " Reason: {$reason}";
            }
            $this->create($document->owner_id, $document->id, $message);
            
            // Send email notification to owner
            try {
                $owner = User::find($document->owner_id);
                if ($owner && $owner->email) {
                    \Log::info('Sending rejection email to owner', [
                        'owner_email' => $owner->email,
                        'document_title' => $document->title
                    ]);
                    Mail::to($owner->email)->send(new DocumentRejectedMail($document->title, $reason, $rejectorName));
                    \Log::info('Rejection email sent successfully to owner', ['email' => $owner->email]);
                } else {
                    \Log::warning('Owner not found or has no email for rejection', [
                        'owner_id' => $document->owner_id,
                        'has_email' => $owner ? ($owner->email ? 'yes' : 'no') : 'user_not_found'
                    ]);
                }
            } catch (\Exception $e) {
                \Log::error('Failed to send document rejected email to owner: ' . $e->getMessage(), [
                    'owner_id' => $document->owner_id,
                    'document_id' => $document->id,
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }

        // Find the handler who sent the document (most recent route before rejection)
        try {
            $senderRoute = DocumentRoute::where('document_id', $document->id)
                ->where('new_status', 'Sent')
                ->whereNotNull('from_user_id')
                ->orderBy('created_at', 'desc')
                ->first();

            if ($senderRoute && $senderRoute->from_user_id) {
                $senderHandler = User::find($senderRoute->from_user_id);
                
                // Only notify if sender is a handler and different from the rejector
                if ($senderHandler && $senderHandler->role === 'handler' && (!$rejector || $senderHandler->id !== $rejector->id)) {
                    $message = "Document '{$document->title}' that you sent has been rejected.";
                    if ($reason) {
                        $message .= " Reason: {$reason}";
                    }
                    $this->create($senderHandler->id, $document->id, $message);
                    
                    // Send email notification to sender handler
                    if ($senderHandler->email) {
                        \Log::info('Sending rejection email to sender handler', [
                            'handler_email' => $senderHandler->email,
                            'document_title' => $document->title,
                            'handler_id' => $senderHandler->id
                        ]);
                        Mail::to($senderHandler->email)->send(new DocumentRejectedMail($document->title, $reason, $rejectorName));
                        \Log::info('Rejection email sent successfully to sender handler', ['email' => $senderHandler->email]);
                    } else {
                        \Log::warning('Sender handler has no email address', ['handler_id' => $senderHandler->id]);
                    }
                }
            }
        } catch (\Exception $e) {
            \Log::error('Failed to notify sender handler about rejection: ' . $e->getMessage(), [
                'document_id' => $document->id,
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Notify sender department when document is archived
     * 
     * @param Document $document
     * @param string|null $archiverName
     * @return void
     */
    public function notifyArchive(Document $document, ?string $archiverName = null): void
    {
        if ($document->owner_id) {
            $this->create(
                $document->owner_id,
                $document->id,
                "Document '{$document->title}' has been archived by the recipient."
            );
            
            // Send email notification
            try {
                $owner = User::find($document->owner_id);
                if ($owner && $owner->email) {
                    \Log::info('Sending archive email to owner', [
                        'owner_email' => $owner->email,
                        'document_title' => $document->title
                    ]);
                    Mail::to($owner->email)->send(new DocumentArchivedMail($document->title, $archiverName));
                    \Log::info('Archive email sent successfully', ['email' => $owner->email]);
                } else {
                    \Log::warning('Owner not found or has no email for archive', [
                        'owner_id' => $document->owner_id,
                        'has_email' => $owner ? ($owner->email ? 'yes' : 'no') : 'user_not_found'
                    ]);
                }
            } catch (\Exception $e) {
                \Log::error('Failed to send document archived email: ' . $e->getMessage(), [
                    'owner_id' => $document->owner_id,
                    'document_id' => $document->id,
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }
    }

    /**
     * Notify sender department when document is deleted
     * 
     * @param Document $document
     * @param string|null $deleterName
     * @return void
     */
    public function notifyDelete(Document $document, ?string $deleterName = null): void
    {
        if ($document->owner_id) {
            $this->create(
                $document->owner_id,
                $document->id,
                "Document '{$document->title}' has been deleted."
            );
            
            // Send email notification
            try {
                $owner = User::find($document->owner_id);
                if ($owner && $owner->email) {
                    \Log::info('Sending delete email to owner', [
                        'owner_email' => $owner->email,
                        'document_title' => $document->title
                    ]);
                    Mail::to($owner->email)->send(new DocumentDeletedMail($document->title, $deleterName));
                    \Log::info('Delete email sent successfully', ['email' => $owner->email]);
                } else {
                    \Log::warning('Owner not found or has no email for delete', [
                        'owner_id' => $document->owner_id,
                        'has_email' => $owner ? ($owner->email ? 'yes' : 'no') : 'user_not_found'
                    ]);
                }
            } catch (\Exception $e) {
                \Log::error('Failed to send document deleted email: ' . $e->getMessage(), [
                    'owner_id' => $document->owner_id,
                    'document_id' => $document->id,
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }
    }
}

