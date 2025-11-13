<?php

namespace App\Services;

use App\Models\DocumentLog;
use App\Models\Document;
use App\Models\User;

class DocumentLogService
{
    /**
     * Create a document log entry
     * 
     * @param int $documentId
     * @param int|null $userId
     * @param int|null $departmentId
     * @param string $action
     * @param string|null $remarks
     * @return DocumentLog
     */
    public function log(int $documentId, ?int $userId, ?int $departmentId, string $action, ?string $remarks = null): DocumentLog
    {
        return DocumentLog::create([
            'document_id' => $documentId,
            'user_id' => $userId,
            'department_id' => $departmentId,
            'action' => $action,
            'remarks' => $remarks,
        ]);
    }

    /**
     * Log document upload
     * 
     * @param Document $document
     * @param User $user
     * @return DocumentLog
     */
    public function logUpload(Document $document, User $user): DocumentLog
    {
        return $this->log(
            $document->id,
            $user->id,
            $user->department_id,
            'upload',
            "Document '{$document->title}' uploaded by {$user->name}"
        );
    }

    /**
     * Log document send action
     * 
     * @param Document $document
     * @param User $user
     * @param string $remarks
     * @return DocumentLog
     */
    public function logSend(Document $document, User $user, string $remarks = ''): DocumentLog
    {
        return $this->log(
            $document->id,
            $user->id,
            $user->department_id,
            'send',
            $remarks ?: "Document sent by {$user->name}"
        );
    }

    /**
     * Log document receive action
     * 
     * @param Document $document
     * @param User $user
     * @param string $remarks
     * @return DocumentLog
     */
    public function logReceive(Document $document, User $user, string $remarks = ''): DocumentLog
    {
        return $this->log(
            $document->id,
            $user->id,
            $user->department_id,
            'receive',
            $remarks ?: "Document received by {$user->name}"
        );
    }

    /**
     * Log document reject action
     * 
     * @param Document $document
     * @param User $user
     * @param string $reason
     * @return DocumentLog
     */
    public function logReject(Document $document, User $user, string $reason = ''): DocumentLog
    {
        return $this->log(
            $document->id,
            $user->id,
            $user->department_id,
            'reject',
            $reason ?: "Document rejected by {$user->name}"
        );
    }

    /**
     * Log document archive action
     * 
     * @param Document $document
     * @param User $user
     * @return DocumentLog
     */
    public function logArchive(Document $document, User $user): DocumentLog
    {
        return $this->log(
            $document->id,
            $user->id,
            $user->department_id,
            'archive',
            "Document archived by {$user->name}"
        );
    }

    /**
     * Log document delete action
     * 
     * @param Document $document
     * @param User $user
     * @return DocumentLog
     */
    public function logDelete(Document $document, User $user): DocumentLog
    {
        return $this->log(
            $document->id,
            $user->id,
            $user->department_id,
            'delete',
            "Document deleted by {$user->name}"
        );
    }
}

