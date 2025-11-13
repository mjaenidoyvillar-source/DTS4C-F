<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;

class DocumentController extends Controller
{
    // Shared methods accessible by all roles for viewing documents
    public function file(Document $document)
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        
        // Prevent handlers from accessing files of same-dept owner-to-owner documents
        if ($user && $user->role === 'handler' && $user->department_id) {
            // Check if this is a same-dept owner-to-owner document (bypasses handler)
            $isSameDeptOwnerToOwner = (
                $document->department_id === $document->receiving_department_id &&
                $document->department_id === $user->department_id &&
                $document->current_handler_id === null &&
                $document->current_status === 'pending_recipient_owner'
            );
            
            if ($isSameDeptOwnerToOwner) {
                if (request()->expectsJson() || request()->is('api/*')) {
                    return response()->json([
                        'message' => 'This document is not accessible. It was sent directly between owners in the same department and bypasses handler processing.',
                    ], 403);
                }
                abort(403, 'This document is not accessible. It was sent directly between owners in the same department.');
            }
        }
        
        if (!$document->file_data) {
            if (request()->expectsJson() || request()->is('api/*')) {
                return response()->json(['message' => 'File not found'], 404);
            }
            abort(404);
        }
        return response($document->file_data)
            ->header('Content-Type', $document->file_mime ?? 'application/octet-stream')
            ->header('Content-Disposition', 'attachment; filename="'.($document->file_name ?? 'document'). '"')
            ->header('Content-Length', (string) ($document->file_size ?? strlen($document->file_data)));
    }

    public function view(Request $request, Document $document)
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        
        // Prevent handlers from viewing same-dept owner-to-owner documents
        if ($user && $user->role === 'handler' && $user->department_id) {
            // Check if this is a same-dept owner-to-owner document (bypasses handler)
            $isSameDeptOwnerToOwner = (
                $document->department_id === $document->receiving_department_id &&
                $document->department_id === $user->department_id &&
                $document->current_handler_id === null &&
                $document->current_status === 'pending_recipient_owner'
            );
            
            if ($isSameDeptOwnerToOwner) {
                if ($request->expectsJson() || $request->is('api/*')) {
                    return response()->json([
                        'message' => 'This document is not accessible. It was sent directly between owners in the same department and bypasses handler processing.',
                    ], 403);
                }
                abort(403, 'This document is not accessible. It was sent directly between owners in the same department.');
            }
        }
        
        $document->load(['department', 'owner', 'currentHandler']);
        
        $receivingDept = null;
        if ($document->receiving_department_id) {
            $receivingDept = \App\Models\Department::find($document->receiving_department_id);
        }

        // Return JSON for API requests
        if ($request->expectsJson() || $request->is('api/*')) {
            return $this->details($document);
        }
        
        return view('documents.viewer', [
            'document' => $document,
            'receivingDept' => $receivingDept,
        ]);
    }

    public function details(Document $document)
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        
        // Prevent handlers from viewing same-dept owner-to-owner documents
        if ($user && $user->role === 'handler' && $user->department_id) {
            // Check if this is a same-dept owner-to-owner document (bypasses handler)
            $isSameDeptOwnerToOwner = (
                $document->department_id === $document->receiving_department_id &&
                $document->department_id === $user->department_id &&
                $document->current_handler_id === null &&
                $document->current_status === 'pending_recipient_owner'
            );
            
            if ($isSameDeptOwnerToOwner) {
                return response()->json([
                    'message' => 'This document is not accessible. It was sent directly between owners in the same department and bypasses handler processing.',
                ], 403);
            }
        }
        
        $document->load(['department', 'owner', 'currentHandler']);
        
        $receivingDept = null;
        if ($document->receiving_department_id) {
            $receivingDept = \App\Models\Department::find($document->receiving_department_id);
        }
        
        // Ensure owner information is properly retrieved
        $ownerName = null;
        if ($document->owner_id && $document->owner) {
            // Use name, fallback to email
            $ownerName = $document->owner->name ?? $document->owner->email ?? null;
        } elseif ($document->owner_id) {
            // If owner_id exists but relationship failed, try direct lookup
            $owner = \App\Models\User::find($document->owner_id);
            if ($owner) {
                $ownerName = $owner->name ?? $owner->email ?? null;
            }
        }
        
        // Get target owner information
        $targetOwnerName = null;
        if ($document->target_owner_id) {
            $targetOwner = \App\Models\User::find($document->target_owner_id);
            if ($targetOwner) {
                // Use name, fallback to email
                $targetOwnerName = $targetOwner->name ?? $targetOwner->email ?? null;
            }
        }
        
        // Get rejection reason if document is rejected
        $rejectionReason = null;
        if (strtolower($document->current_status) === 'rejected') {
            $rejectLog = \App\Models\DocumentLog::where('document_id', $document->id)
                ->where('action', 'reject')
                ->latest()
                ->first();
            if ($rejectLog && $rejectLog->remarks) {
                $rejectionReason = $rejectLog->remarks;
            }
        }
        
        return response()->json([
            'id' => $document->id,
            'title' => $document->title,
            'document_type' => $document->document_type,
            'description' => $document->description,
            'purpose' => $document->purpose,
            'department' => $document->department->name ?? null,
            'receiving_department' => $receivingDept->name ?? null,
            'owner' => $ownerName,
            'owner_id' => $document->owner_id, // Include for debugging
            'target_owner_id' => $document->target_owner_id,
            'target_owner' => $targetOwnerName,
            'current_handler' => $document->currentHandler->name ?? null,
            'current_status' => $document->current_status,
            'rejection_reason' => $rejectionReason,
            'file_name' => $document->file_name,
            'file_mime' => $document->file_mime,
            'file_size' => $document->file_size,
            'has_file' => !empty($document->file_data),
            'file_url' => route('documents.file', $document),
            'created_at' => $document->created_at->format('Y-m-d H:i:s'),
            'qr_code_url' => route('documents.details', $document), // QR code points to document details
        ]);
    }

    /**
     * Public QR code access - redirects to login if not authenticated, or shows document if authenticated
     */
    public function qrAccess(Document $document)
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        
        // If user is authenticated, redirect to document view
        if ($user) {
            return redirect()->route('documents.view', $document);
        }
        
        // If not authenticated, redirect to login with document ID in session
        return redirect()->route('login')->with('qr_scan', [
            'message' => 'Please log in to view document details.',
            'document_id' => $document->id
        ]);
    }
}
