<?php

namespace App\Http\Controllers\Traits;

trait HandlesApiRequests
{
    /**
     * Check if request is an API request
     */
    protected function isApiRequest($request)
    {
        return $request->expectsJson() || $request->is('api/*') || $request->wantsJson();
    }

    /**
     * Format document for API response
     * @param \App\Models\Document $document
     * @param \App\Models\User|null $viewingUser Optional user viewing the document (for status capping)
     */
    protected function formatDocumentForApi($document, $viewingUser = null)
    {
        $document->load(['department', 'owner', 'currentHandler']);
        $receivingDept = null;
        if ($document->receiving_department_id) {
            $receivingDept = \App\Models\Department::find($document->receiving_department_id);
        }
        
        // Get target owner information
        $targetOwnerName = null;
        if ($document->target_owner_id) {
            $targetOwner = \App\Models\User::find($document->target_owner_id);
            if ($targetOwner) {
                $targetOwnerName = $targetOwner->name ?? $targetOwner->email ?? null;
            }
        }
        
        // Cap status at "received" for sender owners after recipient owner receives
        // The sender should not know statuses after "received" on the recipient side
        $status = $document->current_status;
        if ($viewingUser && $document->owner_id === $viewingUser->id) {
            // If the document is now with a recipient owner (not the sender) and status is "received" or later,
            // always show "received" to the sender.
            $isWithRecipientOwner = $document->current_owner_id && $document->current_owner_id !== $viewingUser->id;
            $statusLower = is_string($status) ? strtolower($status) : $status;
            $postReceiveStatuses = ['received', 'archived', 'deleted', 'completed'];
            if ($isWithRecipientOwner && in_array($statusLower, $postReceiveStatuses, true)) {
                $status = 'received';
            }
        }
        
        // Get rejection reason if document is rejected
        $rejectionReason = null;
        if (strtolower($status) === 'rejected') {
            $rejectLog = \App\Models\DocumentLog::where('document_id', $document->id)
                ->where('action', 'reject')
                ->latest()
                ->first();
            if ($rejectLog && $rejectLog->remarks) {
                $rejectionReason = $rejectLog->remarks;
            }
        }
        
        return [
            'id' => $document->id,
            'title' => $document->title,
            'document_type' => $document->document_type,
            'description' => $document->description,
            'purpose' => $document->purpose,
            'current_status' => $status,
            'rejection_reason' => $rejectionReason,
            'department' => $document->department ? ($document->department->name ?? null) : null,
            'department_id' => $document->department_id,
            'receiving_department' => $receivingDept->name ?? null,
            'receiving_department_id' => $document->receiving_department_id,
            'owner' => $document->owner ? ($document->owner->name ?? $document->owner->email ?? null) : null,
            'owner_id' => $document->owner_id,
            'target_owner_id' => $document->target_owner_id,
            'target_owner' => $targetOwnerName,
            'current_handler' => $document->currentHandler ? ($document->currentHandler->name ?? $document->currentHandler->email ?? null) : null,
            'current_handler_id' => $document->current_handler_id,
            'file_name' => $document->file_name,
            'file_mime' => $document->file_mime,
            'file_size' => $document->file_size,
            'has_file' => !empty($document->file_data),
            'file_url' => $document->file_data ? '/api/documents/' . $document->id . '/file' : null,
            'created_at' => $document->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $document->updated_at->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Format paginated documents for API
     * @param \Illuminate\Contracts\Pagination\LengthAwarePaginator $documents
     * @param \App\Models\User|null $viewingUser Optional user viewing the documents (for status capping)
     */
    protected function formatPaginatedDocuments($documents, $viewingUser = null)
    {
        return [
            'data' => $documents->getCollection()->map(function ($doc) use ($viewingUser) {
                return $this->formatDocumentForApi($doc, $viewingUser);
            })->values(),
            'current_page' => $documents->currentPage(),
            'last_page' => $documents->lastPage(),
            'per_page' => $documents->perPage(),
            'total' => $documents->total(),
        ];
    }
}

