<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Document;
use Illuminate\Http\Request;

class DocumentTrackingController extends Controller
{
    /**
     * Track a document by QR code
     * 
     * @param Request $request
     * @param string $qrCode
     * @return \Illuminate\Http\JsonResponse
     */
    public function track(Request $request, $qrCode)
    {
        // Extract document ID from QR code
        // QR code format: {random}_{documentId}
        $parts = explode('_', $qrCode);
        if (count($parts) < 2) {
            return response()->json(['message' => 'Invalid QR code'], 404);
        }

        $documentId = end($parts);
        $document = Document::withTrashed()
            ->with(['department', 'receivingDepartment', 'owner', 'currentHandler', 'currentOwner', 'targetOwner', 'logs.user', 'logs.department'])
            ->find($documentId);

        if (!$document) {
            return response()->json(['message' => 'Document not found'], 404);
        }

        return response()->json([
            'document' => [
                'id' => $document->id,
                'title' => $document->title,
                'description' => $document->description,
                'document_type' => $document->document_type,
                'purpose' => $document->purpose,
                'current_status' => $document->current_status,
                'created_at' => $document->created_at->format('Y-m-d H:i:s'),
            ],
            'metadata' => [
                'sender_department' => $document->department->name ?? null,
                'sender_department_id' => $document->department_id,
                'recipient_department' => $document->receivingDepartment->name ?? null,
                'recipient_department_id' => $document->receiving_department_id,
                'owner' => $document->owner->name ?? $document->owner->email ?? null,
                'owner_id' => $document->owner_id,
                'target_owner' => $document->targetOwner->name ?? $document->targetOwner->email ?? null,
                'target_owner_id' => $document->target_owner_id,
                'current_handler' => $document->currentHandler->name ?? $document->currentHandler->email ?? null,
                'current_handler_id' => $document->current_handler_id,
                'current_owner' => $document->currentOwner->name ?? $document->currentOwner->email ?? null,
                'current_owner_id' => $document->current_owner_id,
            ],
            'history' => $document->logs->map(function($log) {
                return [
                    'action' => $log->action,
                    'remarks' => $log->remarks,
                    'user' => $log->user->name ?? $log->user->email ?? null,
                    'department' => $log->department->name ?? null,
                    'created_at' => $log->created_at->format('Y-m-d H:i:s'),
                ];
            }),
        ]);
    }
}
