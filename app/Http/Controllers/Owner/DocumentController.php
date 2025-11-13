<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HandlesApiRequests;
use App\Models\Document;
use App\Models\DocumentRoute;
use App\Services\AuditLogger;
use App\Services\QrCodeService;
use App\Services\NotificationService;
use App\Services\DocumentLogService;
use App\Mail\DocumentUpdatedMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class DocumentController extends Controller
{
    use HandlesApiRequests;

    protected $qrCodeService;
    protected $notificationService;
    protected $documentLogService;

    public function __construct(
        QrCodeService $qrCodeService,
        NotificationService $notificationService,
        DocumentLogService $documentLogService
    ) {
        $this->qrCodeService = $qrCodeService;
        $this->notificationService = $notificationService;
        $this->documentLogService = $documentLogService;
    }
    public function myDocuments(Request $request)
    {
        $user = Auth::user();
        // Show all documents created by this user, EXCEPT those archived by recipients
        // Senders should only see status up to the point of receipt, not after recipient archives
        // Get document IDs that were archived by someone other than the creator (recipient archived it)
        $archivedByOthers = \App\Models\AuditLog::where('action_type', 'Archive')
            ->whereNotNull('document_id')
            ->where('user_id', '!=', $user->id) // Archived by someone other than creator
            ->pluck('document_id')
            ->unique();
        
        // Exclude documents archived by recipients (not the sender)
        // This way sender only sees status up to receipt, not after recipient archives
        $query = Document::where('owner_id', $user->id)
            ->with(['currentHandler', 'targetOwner']);
        
        // Exclude documents archived by others (recipients)
        if (!$archivedByOthers->isEmpty()) {
            $query->whereNotIn('id', $archivedByOthers);
        }
        
        $documents = $query->orderByDesc('created_at')->paginate(15);

        // Transform documents to cap status at "received" for sender
        // Sender should not see "archived" status if recipient archived it
        $documents->getCollection()->transform(function($doc) use ($user, $archivedByOthers) {
            // If document was archived by recipient, show status as "received" instead
            if ($archivedByOthers->contains($doc->id) && $doc->current_status === 'archived') {
                $doc->current_status = 'received';
            }
            // Also cap any status beyond "received" to "received" for sender
            if (in_array($doc->current_status, ['archived']) && $doc->current_owner_id !== $user->id) {
                // If recipient archived it, show as received
                $doc->current_status = 'received';
            }
            $doc->current_handler_name = $doc->currentHandler ? ($doc->currentHandler->name ?? $doc->currentHandler->email ?? 'Unknown') : null;
            $doc->target_owner_name = $doc->targetOwner ? ($doc->targetOwner->name ?? $doc->targetOwner->email ?? 'Unknown') : null;
            
            // Get rejection reason if document is rejected
            if (strtolower($doc->current_status) === 'rejected') {
                $rejectLog = \App\Models\DocumentLog::where('document_id', $doc->id)
                    ->where('action', 'reject')
                    ->latest()
                    ->first();
                if ($rejectLog && $rejectLog->remarks) {
                    $doc->rejection_reason = $rejectLog->remarks;
                } else {
                    $doc->rejection_reason = null;
                }
            } else {
                $doc->rejection_reason = null;
            }
            
            return $doc;
        });

        if ($this->isApiRequest($request)) {
            return response()->json($this->formatPaginatedDocuments($documents, $user));
        }

        return view('owner.my_documents', [
            'user' => $user,
            'documents' => $documents,
        ]);
    }

    public function deleted(Request $request)
    {
        $user = Auth::user();
        if (($user->role ?? null) !== 'owner') {
            if ($this->isApiRequest($request)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
            abort(403);
        }
        
        // Show only documents deleted by this user (not by other owners)
        // Get deleted documents from audit logs where this user performed the delete
        $deletedLogs = \App\Models\AuditLog::where('action_type', 'Delete')
            ->where('user_id', $user->id)
            ->whereNotNull('document_id')
            ->with(['user'])
            ->latest()
            ->paginate(15);

        // Since documents are hard-deleted, we reconstruct basic info from audit logs
        $deletedDocuments = $deletedLogs->getCollection()->map(function($log) {
            return (object)[
                'id' => $log->document_id,
                'title' => $this->extractTitleFromDescription($log->description),
                'deleted_by' => $log->user ? ($log->user->name ?? $log->user->email ?? 'Unknown') : 'Unknown',
                'deleted_at' => $log->created_at,
                'description' => $log->description,
            ];
        });

        if ($this->isApiRequest($request)) {
            return response()->json([
                'data' => $deletedDocuments,
                'current_page' => $deletedLogs->currentPage(),
                'last_page' => $deletedLogs->lastPage(),
                'per_page' => $deletedLogs->perPage(),
                'total' => $deletedLogs->total(),
            ]);
        }

        return view('owner.deleted', [
            'user' => $user,
            'deletedDocuments' => $deletedDocuments,
            'pagination' => $deletedLogs,
        ]);
    }

    private function extractTitleFromDescription($description)
    {
        // Try to extract title from description like "Document 'Title' deleted"
        if (preg_match("/Document '([^']+)' deleted/", $description, $matches)) {
            return $matches[1];
        }
        return 'Document';
    }

    public function register(Request $request)
    {
        $user = Auth::user();
        if (($user->role ?? null) !== 'owner') {
            if ($this->isApiRequest($request)) {
                return response()->json(['message' => 'Only owners can register documents'], 403);
            }
            abort(403, 'Only owners can register documents');
        }
        $rules = [
            'title' => 'required|string|max:255',
            'document_type' => 'required|string|max:100',
            'description' => 'nullable|string',
            'purpose' => 'required|string|max:255',
            'receiving_department_id' => 'required|exists:departments,id',
            'target_owner_id' => 'required|exists:users,id',
            'file' => 'required|file|mimes:doc,docx,pdf,xls,xlsx,ppt,pptx,txt,csv,rtf,odt|max:51200', // 50MB limit
        ];
        $messages = [
            'receiving_department_id.required' => 'Please select a :attribute.',
            'receiving_department_id.exists' => 'The selected :attribute is invalid.',
            'target_owner_id.required' => 'Please select a :attribute.',
            'target_owner_id.exists' => 'The selected :attribute is invalid.',
            'file.required' => 'Please select a file to upload.',
            'file.mimes' => 'The file type is not supported. Please upload one of the following formats: DOC, DOCX, PDF, XLS, XLSX, PPT, PPTX, TXT, CSV, RTF, or ODT.',
            'file.max' => 'The file is too large. Maximum file size is 50MB. Please choose a smaller file.',
        ];
        $attributes = [
            'title' => 'Title',
            'document_type' => 'Document Type',
            'description' => 'Description',
            'purpose' => 'Purpose',
            'receiving_department_id' => 'Receiving Department',
            'target_owner_id' => 'Recipient Owner',
            'file' => 'File',
        ];
        $validator = Validator::make($request->all(), $rules, $messages, $attributes);
        $data = $validator->validate();
        
        // Validate that target_owner_id is an owner in the receiving_department_id
        $targetOwner = \App\Models\User::find($data['target_owner_id']);
        if (!$targetOwner || $targetOwner->department_id != $data['receiving_department_id'] || $targetOwner->role !== 'owner') {
            if ($this->isApiRequest($request)) {
                return response()->json(['message' => 'Target owner must be an owner in the receiving department'], 422);
            }
            return back()->withErrors(['target_owner_id' => 'Target owner must be an owner in the receiving department'])->withInput();
        }
        
        // Check if sending to same department (direct to owner, bypass handler)
        // Use strict comparison to ensure type matching
        $isSameDepartment = ((int)$data['receiving_department_id'] === (int)$user->department_id);

        $document = null;
        $maxRetries = 3;
        $retryCount = 0;
        
        while ($retryCount < $maxRetries) {
            try {
                DB::transaction(function () use ($user, $request, $data, $isSameDepartment, $targetOwner, &$document) {
                    $uploaded = $request->file('file');
                    
                    // Check file size before reading (prevent memory issues)
                    $fileSize = $uploaded->getSize();
                    if ($fileSize > 50 * 1024 * 1024) { // 50MB limit
                        throw new \Exception('The file is too large. Maximum file size is 50MB. Please choose a smaller file.');
                    }
                    
                    // Read file contents with error handling
                    $fileContents = @file_get_contents($uploaded->getRealPath());
                    if ($fileContents === false) {
                        throw new \Exception('Unable to read the file. The file may be corrupted or inaccessible. Please try selecting a different file.');
                    }
                    
                    // For large files, ensure connection is fresh
                    if ($fileSize > 10 * 1024 * 1024) { // Files larger than 10MB
                        DB::reconnect();
                    }
                
                // Generate QR code
                $qrPath = $this->qrCodeService->generateForDocument(0); // Will update after document creation
                
                if ($isSameDepartment) {
                    // Same department: send directly to target owner (bypass handler)
                    $document = Document::create([
                        'title' => $data['title'],
                        'document_type' => $data['document_type'],
                        'description' => $data['description'] ?? null,
                        'purpose' => $data['purpose'],
                        'file_path' => null,
                        'file_name' => $uploaded->getClientOriginalName(),
                        'file_mime' => $uploaded->getClientMimeType(),
                        'file_size' => $uploaded->getSize(),
                        'file_data' => $fileContents,
                        'qr_path' => $qrPath,
                        'department_id' => $user->department_id,
                        'receiving_department_id' => $data['receiving_department_id'],
                        'target_owner_id' => $data['target_owner_id'],
                        'owner_id' => $user->id,
                        'current_owner_id' => $targetOwner->id,
                        'current_handler_id' => null,
                        'current_status' => 'pending_recipient_owner', // Direct to owner, bypass handler
                    ]);
                    
                    // Update QR code with actual document ID
                    $qrPath = $this->qrCodeService->generateForDocument($document->id);
                    $document->update(['qr_path' => $qrPath]);
                    
                    // Create route for tracking (direct to owner in same department)
                    \App\Models\DocumentRoute::create([
                        'document_id' => $document->id,
                        'from_department_id' => $user->department_id,
                        'to_department_id' => $user->department_id,
                        'from_user_id' => $user->id,
                        'to_user_id' => $targetOwner->id,
                        'target_owner_id' => $targetOwner->id,
                        'new_status' => 'pending_recipient_owner',
                    ]);
                    
                    // Log and notify
                    $this->documentLogService->logUpload($document, $user);
                    $this->notificationService->notifyRecipientOwner($document, $targetOwner->id);
                    AuditLogger::log($document->id, $user->id, 'Register', "Owner {$user->name} registered and sent document '{$document->title}' directly to owner {$targetOwner->name} in the same department.");
                } else {
                    // Different department: assign to handler first
                    $departmentHandler = \App\Models\User::where('department_id', $user->department_id)
                        ->where('role', 'handler')
                        ->where('is_active', true)
                        ->first();
                    
                    if (!$departmentHandler) {
                        \Log::error('No handler found for department', [
                            'department_id' => $user->department_id,
                            'user_id' => $user->id,
                            'receiving_department_id' => $data['receiving_department_id']
                        ]);
                        throw new \Exception('No handler found in your department. Please assign a handler to your department first.');
                    }
                    
                    \Log::info('Handler assigned to document', [
                        'document_title' => $data['title'],
                        'handler_id' => $departmentHandler->id,
                        'handler_name' => $departmentHandler->name,
                        'department_id' => $user->department_id,
                        'receiving_department_id' => $data['receiving_department_id']
                    ]);
                    
                    $document = Document::create([
                        'title' => $data['title'],
                        'document_type' => $data['document_type'],
                        'description' => $data['description'] ?? null,
                        'purpose' => $data['purpose'],
                        'file_path' => null,
                        'file_name' => $uploaded->getClientOriginalName(),
                        'file_mime' => $uploaded->getClientMimeType(),
                        'file_size' => $uploaded->getSize(),
                        'file_data' => $fileContents,
                        'qr_path' => $qrPath,
                        'department_id' => $user->department_id,
                        'receiving_department_id' => $data['receiving_department_id'],
                        'target_owner_id' => $data['target_owner_id'],
                        'owner_id' => $user->id,
                        'current_handler_id' => $departmentHandler->id, // Assign to department handler
                        'current_status' => 'pending_handler_review', // New status flow
                    ]);
                    
                    // Update QR code with actual document ID
                    $qrPath = $this->qrCodeService->generateForDocument($document->id);
                    $document->update(['qr_path' => $qrPath]);
                    
                    // Log and notify
                    $this->documentLogService->logUpload($document, $user);
                    $this->notificationService->notifySenderHandler($document);
                    AuditLogger::log($document->id, $user->id, 'Register', "Owner {$user->name} registered document '{$document->title}'.");
                }
                
                // Verify owner_id was set
                if (!$document->owner_id) {
                    throw new \Exception('Failed to set document owner');
                }
            });
            
                // Success - break out of retry loop
                break;
            } catch (\PDOException $e) {
                // Handle MySQL "server has gone away" error
                if (str_contains($e->getMessage(), 'MySQL server has gone away') || 
                    str_contains($e->getMessage(), '2006')) {
                    $retryCount++;
                    if ($retryCount >= $maxRetries) {
                        // Max retries reached
                        if ($this->isApiRequest($request)) {
                            return response()->json([
                                'message' => 'The file upload failed due to a server error. The file may be too large. Please try again with a smaller file.',
                                'error' => config('app.debug') ? $e->getMessage() : 'Server error',
                            ], 503);
                        }
                        return back()->withErrors(['file' => 'The file upload failed due to a server error. The file may be too large. Please try again with a smaller file.'])->withInput();
                    }
                    // Reconnect and retry
                    DB::reconnect();
                    sleep(1); // Wait 1 second before retry
                    continue;
                }
                // Other PDO exceptions - rethrow
                throw $e;
            } catch (\Exception $e) {
                // Non-PDO exceptions - don't retry
                if ($this->isApiRequest($request)) {
                    return response()->json([
                        'message' => $e->getMessage(),
                    ], 422);
                }
                return back()->withErrors(['error' => $e->getMessage()])->withInput();
            }
        }
        
        if ($document) {
            if ($this->isApiRequest($request)) {
                return response()->json([
                    'message' => 'Document registered successfully',
                    'document' => $this->formatDocumentForApi($document, $user),
                ]);
            }
            return back()->with('status', 'Document registered');
        } else {
            if ($this->isApiRequest($request)) {
                return response()->json([
                    'message' => 'Failed to register document after multiple attempts. Please try again.',
                ], 500);
            }
            return back()->withErrors(['error' => 'Failed to register document. Please try again.'])->withInput();
        }
    }

    public function released(Request $request)
    {
        $user = Auth::user();
        if (($user->role ?? null) !== 'owner') {
            if ($this->isApiRequest($request)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
            abort(403);
        }
        // Show documents that are Registered, Sent, or On Hold (so owner can update them)
        $documents = Document::where('owner_id', $user->id)
            ->whereIn('current_status', ['Registered', 'Sent', 'On Hold'])
            ->orderByDesc('created_at')
            ->paginate(15);

        // Check which documents can be edited (not sent to another handler)
        foreach ($documents as $doc) {
            $hasBeenSentToOtherHandler = DocumentRoute::where('document_id', $doc->id)
                ->where('to_department_id', '!=', $doc->department_id)
                ->exists();
            
            $doc->can_be_edited = in_array($doc->current_status, ['Registered', 'On Hold']) || 
                                  ($doc->current_status === 'Sent' && !$hasBeenSentToOtherHandler);
        }

        if ($this->isApiRequest($request)) {
            return response()->json($this->formatPaginatedDocuments($documents, $user));
        }

        return view('owner.released', [
            'user' => $user,
            'documents' => $documents,
        ]);
    }

    public function forReview(Request $request)
    {
        $user = Auth::user();
        if (($user->role ?? null) !== 'owner') {
            if ($this->isApiRequest($request)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
            abort(403);
        }
        $documents = Document::where(function($q) use ($user) {
                $q->where('current_handler_id', $user->id)
                  ->where('current_status', 'For Review');
            })
            ->orderByDesc('created_at')
            ->paginate(15);

        if ($this->isApiRequest($request)) {
            return response()->json($this->formatPaginatedDocuments($documents, $user));
        }

        return view('owner.for_review', [
            'user' => $user,
            'documents' => $documents,
        ]);
    }

    /**
     * Incoming documents for recipient owner (API)
     * Shows documents forwarded by handlers that are waiting for the owner to receive,
     * as well as those already received by the owner.
     */
    public function incoming(Request $request)
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                \Log::warning('Incoming documents: No authenticated user', [
                    'path' => $request->path(),
                    'auth_guard' => Auth::getDefaultDriver(),
                ]);
                return response()->json(['message' => 'Unauthenticated'], 401);
            }
            
            if (($user->role ?? null) !== 'owner') {
                return response()->json(['message' => 'Unauthorized. Only owners can access this endpoint.'], 403);
            }

            // Get documents forwarded to this owner (recipient owner)
            // Incoming should ONLY show documents waiting to be received
            // Exclude rejected documents - receiving owners should not see rejected documents
            $documents = Document::with(['department', 'receivingDepartment', 'owner', 'currentHandler'])
                ->where('current_status', 'pending_recipient_owner')
                ->where(function($q) use ($user) {
                    // Show if explicitly assigned to this owner
                    $q->where('current_owner_id', $user->id)
                      // Or if the document's target owner is this user (fallback if assignment wasn't set yet)
                      ->orWhere('target_owner_id', $user->id);
                })
                ->where('owner_id', '!=', $user->id) // Only show documents where user is NOT the sender
                ->orderByDesc('updated_at')
                ->paginate(15);

            if ($this->isApiRequest($request)) {
                return response()->json($this->formatPaginatedDocuments($documents));
            }

            return view('owner.incoming', [
                'user' => $user,
                'documents' => $documents,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in incoming method: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id(),
            ]);
            return response()->json([
                'message' => 'An error occurred while loading incoming documents',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Received documents for recipient owner (API-style)
     */
    public function receivedList(Request $request)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }
            if (($user->role ?? null) !== 'owner') {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
            // Only show received documents where user is the receiving owner (not the sender)
            // Exclude rejected documents - receiving owners should not see rejected documents
            $query = Document::with(['department', 'receivingDepartment', 'owner', 'currentHandler'])
                ->where('current_owner_id', $user->id)
                ->where('current_status', 'received')
                ->where('owner_id', '!=', $user->id); // Only show documents where user is NOT the sender
            
            // Apply filters
            if ($request->filled('document_type')) {
                $query->where('document_type', $request->document_type);
            }
            
            if ($request->filled('department_id')) {
                $deptId = $request->department_id;
                $query->where(function($q) use ($deptId) {
                    $q->where('receiving_department_id', $deptId)
                      ->orWhere('department_id', $deptId);
                });
            }
            
            if ($request->filled('status')) {
                $query->where('current_status', $request->status);
            }
            
            if ($request->filled('search')) {
                $search = trim($request->search);
                if (!empty($search)) {
                    $query->where(function($q) use ($search) {
                        $q->where('title', 'like', '%' . $search . '%')
                          ->orWhere('description', 'like', '%' . $search . '%')
                          ->orWhere('purpose', 'like', '%' . $search . '%');
                    });
                }
            }
            
            $documents = $query->orderByDesc('updated_at')->paginate(15);
            return response()->json($this->formatPaginatedDocuments($documents));
        } catch (\Exception $e) {
            \Log::error('Error in receivedList: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to load received documents',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    public function acceptReview(Request $request, Document $document)
    {
        $user = Auth::user();
        if (($user->role ?? null) !== 'owner') {
            return response()->json(['message' => 'Only owners can accept documents'], 403);
        }
        if ($document->current_handler_id !== $user->id || $document->current_status !== 'For Review') {
            return response()->json(['message' => 'Document not forwarded to you'], 403);
        }

        DB::transaction(function () use ($user, $document) {
            $document->update([
                'current_status' => 'Completed',
            ]);

            $route = DocumentRoute::where('document_id', $document->id)
                ->where('to_user_id', $user->id)
                ->where('new_status', 'For Review')
                ->latest()
                ->first();
            
            if ($route) {
                $route->update(['new_status' => 'Completed']);
            }

            AuditLogger::log($document->id, $user->id, 'Accept', "Owner {$user->name} accepted and completed review of document '{$document->title}'.");
        });

        if ($this->isApiRequest($request)) {
            return response()->json([
                'message' => 'Document accepted and marked as completed',
                'document' => $this->formatDocumentForApi($document->fresh(), $user),
            ]);
        }
        return back()->with('status', 'Document accepted and completed');
    }

    public function declineReview(Request $request, Document $document)
    {
        $user = Auth::user();
        if (($user->role ?? null) !== 'owner') {
            return response()->json(['message' => 'Only owners can decline documents'], 403);
        }
        if ($document->current_handler_id !== $user->id || $document->current_status !== 'For Review') {
            return response()->json(['message' => 'Document not forwarded to you'], 403);
        }

        $reason = $request->input('reason', 'No reason provided');

        DB::transaction(function () use ($user, $document, $reason) {
            $document->update([
                'current_status' => 'In Progress',
                'current_handler_id' => null,
            ]);

            $route = DocumentRoute::where('document_id', $document->id)
                ->where('to_user_id', $user->id)
                ->where('new_status', 'For Review')
                ->latest()
                ->first();
            
            if ($route) {
                $route->update(['new_status' => 'Declined']);
            }

            AuditLogger::log($document->id, $user->id, 'Decline', "Owner {$user->name} declined review of document '{$document->title}'. Reason: {$reason}");
        });

        if ($this->isApiRequest($request)) {
            return response()->json([
                'message' => 'Document declined',
                'document' => $this->formatDocumentForApi($document->fresh()),
            ]);
        }
        return back()->with('status', 'Document declined');
    }

    public function complete(Request $request)
    {
        $user = Auth::user();
        if (($user->role ?? null) !== 'owner') {
            if ($this->isApiRequest($request)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
            abort(403);
        }
        
        // Get document IDs that were archived by recipients (not the sender)
        $archivedByOthers = \App\Models\AuditLog::where('action_type', 'Archive')
            ->whereNotNull('document_id')
            ->where('user_id', '!=', $user->id) // Archived by someone other than creator
            ->pluck('document_id')
            ->unique();
        
        $query = Document::where(function($q) use ($user) {
                $q->where('owner_id', $user->id)
                  ->orWhere(function($q2) use ($user) {
                      $q2->where('current_handler_id', $user->id)
                         ->where('current_status', 'Completed');
                  });
            })
            ->where('current_status', 'Completed');
        
        // Exclude documents archived by recipients (senders should only see up to receipt)
        if (!$archivedByOthers->isEmpty()) {
            $query->whereNotIn('id', $archivedByOthers);
        }
        
        $documents = $query->orderByDesc('created_at')->paginate(15);

        if ($this->isApiRequest($request)) {
            return response()->json($this->formatPaginatedDocuments($documents));
        }

        return view('owner.complete', [
            'user' => $user,
            'documents' => $documents,
        ]);
    }

    public function update(Request $request, Document $document)
    {
        $user = Auth::user();
        if (($user->role ?? null) !== 'owner' || $document->owner_id !== $user->id) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['message' => 'Only the owner can update'], 403);
            }
            return back()->with('status', 'Not allowed');
        }
        
        // Check if document has been sent to another handler (route exists to a different department)
        $hasBeenSentToOtherHandler = DocumentRoute::where('document_id', $document->id)
            ->where('to_department_id', '!=', $document->department_id)
            ->exists();
        
        // Allow updates only if:
        // 1. Status is Registered (not yet processed by handler)
        // 2. Status is On Hold (handler put it on hold, hasn't sent it)
        // 3. Status is Sent BUT document hasn't been sent to another handler yet
        $canEdit = in_array($document->current_status, ['Registered', 'On Hold']) || 
                   ($document->current_status === 'Sent' && !$hasBeenSentToOtherHandler);
        
        if (!$canEdit) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['message' => 'Document cannot be updated. It has already been sent to another handler or is in a locked status.'], 422);
            }
            return back()->with('status', 'Document cannot be updated. It has already been sent to another handler.');
        }
        $updateRules = [
            'title' => 'sometimes|required|string|max:255',
            'document_type' => 'sometimes|required|string|max:100',
            'description' => 'nullable|string',
            'purpose' => 'sometimes|required|string|max:255',
            'receiving_department_id' => 'sometimes|required|exists:departments,id',
        ];
        $updateMessages = [
            'receiving_department_id.required' => 'Please select a :attribute.',
            'receiving_department_id.exists' => 'The selected :attribute is invalid.',
        ];
        $updateAttributes = [
            'title' => 'Title',
            'document_type' => 'Document Type',
            'description' => 'Description',
            'purpose' => 'Purpose',
            'receiving_department_id' => 'Receiving Department',
        ];
        $updateValidator = Validator::make($request->all(), $updateRules, $updateMessages, $updateAttributes);
        $data = $updateValidator->validate();
        $document->update($data);
        AuditLogger::log($document->id, $user->id, 'Update', "Owner {$user->name} updated document '{$document->title}'.");
        
        // Send email notification to document owner
        try {
            // Refresh document to ensure relationships are loaded
            $document->refresh();
            $document->load(['owner', 'currentHandler']);
            
            $documentOwner = $document->owner;
            if ($documentOwner && $documentOwner->email) {
                \Log::info('Sending update email to document owner', [
                    'owner_email' => $documentOwner->email,
                    'document_title' => $document->title
                ]);
                Mail::to($documentOwner->email)->send(new DocumentUpdatedMail($document->title));
                \Log::info('Update email sent successfully to owner', ['email' => $documentOwner->email]);
            } else {
                \Log::warning('Document owner not found or has no email', [
                    'owner_id' => $document->owner_id,
                    'has_email' => $documentOwner ? ($documentOwner->email ? 'yes' : 'no') : 'owner_not_found'
                ]);
            }
            
            // Also send email to current handler if document is being handled
            if ($document->current_handler_id) {
                $currentHandler = $document->currentHandler;
                if ($currentHandler && $currentHandler->email) {
                    \Log::info('Sending update email to current handler', [
                        'handler_email' => $currentHandler->email,
                        'document_title' => $document->title
                    ]);
                    Mail::to($currentHandler->email)->send(new DocumentUpdatedMail($document->title));
                    \Log::info('Update email sent successfully to handler', ['email' => $currentHandler->email]);
                } else {
                    \Log::warning('Current handler not found or has no email', [
                        'handler_id' => $document->current_handler_id,
                        'has_email' => $currentHandler ? ($currentHandler->email ? 'yes' : 'no') : 'handler_not_found'
                    ]);
                }
            }
        } catch (\Exception $e) {
            // Log the error but don't fail the request
            \Log::error('Failed to send document update email: ' . $e->getMessage(), [
                'document_id' => $document->id,
                'trace' => $e->getTraceAsString()
            ]);
        }
        
        if ($this->isApiRequest($request)) {
            return response()->json([
                'message' => 'Document updated successfully',
                'document' => $this->formatDocumentForApi($document->fresh()),
            ]);
        }
        return back()->with('status', 'Document updated');
    }

    public function archived(Request $request)
    {
        $user = Auth::user();
        if (($user->role ?? null) !== 'owner') {
            if ($this->isApiRequest($request)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
            abort(403);
        }
        // Show all documents archived by this user (regardless of who created them)
        // Recipients should see documents they archived even if they didn't create them
        // Senders won't see documents they created that were archived by recipients
        // (handled by excluding from myDocuments and complete views)
        
        // Get document IDs that were archived by this user
        $archivedDocumentIds = \App\Models\AuditLog::where('action_type', 'Archive')
            ->where('user_id', $user->id)
            ->whereNotNull('document_id')
            ->pluck('document_id')
            ->unique();
        
        // Show all documents archived by this user (whether they created it or received it)
        // Exclude rejected documents - receiving owners should not see rejected documents they archived
        $documents = Document::whereIn('id', $archivedDocumentIds->isEmpty() ? [-1] : $archivedDocumentIds)
            ->where('current_status', 'Archived')
            ->where(function($q) use ($user) {
                // Show if user is the original owner (can see rejected)
                $q->where('owner_id', $user->id)
                  // OR if user is receiving owner but document is not rejected
                  ->orWhere(function($q2) use ($user) {
                      $q2->where('owner_id', '!=', $user->id)
                         ->where('current_status', '!=', 'rejected');
                  });
            })
            ->with(['currentHandler', 'targetOwner'])
            ->orderByDesc('updated_at')
            ->paginate(15);

        if ($this->isApiRequest($request)) {
            return response()->json($this->formatPaginatedDocuments($documents));
        }

        return view('owner.archived', [
            'user' => $user,
            'documents' => $documents,
        ]);
    }

    /**
     * Receive document as recipient owner
     */
    public function receive(Request $request, Document $document)
    {
        $user = Auth::user();
        if (($user->role ?? null) !== 'owner') {
            return response()->json(['message' => 'Only owners can receive documents'], 403);
        }

        // Check if document is pending recipient owner and user is the target owner
        if ($document->current_status !== 'pending_recipient_owner' || 
            $document->current_owner_id !== $user->id) {
            return response()->json(['message' => 'Document not available for you to receive'], 403);
        }

        DB::transaction(function () use ($user, $document) {
            $document->update([
                'current_status' => 'received',
                'current_owner_id' => $user->id,
            ]);

            $this->documentLogService->logReceive($document, $user, "Document received by recipient owner {$user->name}");
            $this->notificationService->notifyArchive($document, $user->name); // Notify sender with archiver name
            AuditLogger::log($document->id, $user->id, 'Receive', "Owner {$user->name} received document '{$document->title}'.");
        });

        return response()->json([
            'message' => 'Document received successfully',
            'document' => $this->formatDocumentForApi($document->fresh(), $user),
        ]);
    }

    public function archive(Request $request, Document $document)
    {
        $user = Auth::user();
        if (($user->role ?? null) !== 'owner') {
            return response()->json(['message' => 'Only owners can archive documents'], 403);
        }
        
        // Allow archiving if user is the recipient owner (current_owner_id) OR original creator
        $canArchive = ($document->current_owner_id === $user->id && $document->current_status === 'received') || 
                      ($document->owner_id === $user->id);
        
        if (!$canArchive) {
            return response()->json(['message' => 'You can only archive documents you received or created'], 403);
        }
        
        DB::transaction(function () use ($user, $document) {
            $document->update(['current_status' => 'archived']);
            $this->documentLogService->logArchive($document, $user);
            $this->notificationService->notifyArchive($document, $user->name);
            AuditLogger::log($document->id, $user->id, 'Archive', 'Document archived');
        });
        
        return response()->json([
            'message' => 'Document archived successfully',
            'document' => $this->formatDocumentForApi($document->fresh(), $user),
        ]);
    }

    public function unarchive(Request $request, Document $document)
    {
        $user = Auth::user();
        if (($user->role ?? null) !== 'owner') {
            if ($this->isApiRequest($request)) {
                return response()->json(['message' => 'Only owners can unarchive documents'], 403);
            }
            return back()->with('status', 'Not allowed');
        }
        
        // Allow unarchiving if user is:
        // 1. The original creator (owner_id)
        // 2. The recipient owner who archived it (current_owner_id)
        $canUnarchive = ($document->owner_id === $user->id) || 
                        ($document->current_owner_id === $user->id);
        
        if (!$canUnarchive) {
            if ($this->isApiRequest($request)) {
                return response()->json(['message' => 'You can only unarchive documents you created or received'], 403);
            }
            return back()->with('status', 'Not allowed');
        }
        
        // Check if document is archived (case-insensitive check)
        if (strtolower($document->current_status) !== 'archived') {
            if ($this->isApiRequest($request)) {
                return response()->json(['message' => 'Document is not archived'], 422);
            }
            return back()->with('status', 'Document is not archived');
        }
        
        DB::transaction(function () use ($user, $document) {
            // Restore to "received" status (since it was archived from received)
            $document->update(['current_status' => 'received']);
            $this->documentLogService->log($document->id, $user->id, $user->department_id, 'unarchive', "Document '{$document->title}' unarchived by {$user->name}.");
            AuditLogger::log($document->id, $user->id, 'Unarchive', "Owner {$user->name} unarchived document '{$document->title}'.");
        });
        
        if ($this->isApiRequest($request)) {
            return response()->json([
                'message' => 'Document unarchived successfully',
                'document' => $this->formatDocumentForApi($document->fresh(), $user),
            ]);
        }
        // Redirect to Complete page so user can see the unarchived document
        return redirect()->route('owner.complete')->with('status', 'Document unarchived and moved to Complete');
    }

    public function destroy(Request $request, Document $document)
    {
        $user = Auth::user();
        if (($user->role ?? null) !== 'owner') {
            return response()->json(['message' => 'Only owners can delete documents'], 403);
        }
        
        // Allow deletion if user is the recipient owner (current_owner_id) OR original creator
        $canDelete = ($document->current_owner_id === $user->id && in_array($document->current_status, ['received', 'archived'])) || 
                     ($document->owner_id === $user->id);
        
        if (!$canDelete) {
            return response()->json(['message' => 'You can only delete documents you received or created'], 403);
        }
        
        try {
            $id = $document->id;
            $title = $document->title ?? 'Document';
            
            DB::transaction(function () use ($user, $document, $id, $title) {
                // Log before deletion
                $this->documentLogService->logDelete($document, $user);
                $this->notificationService->notifyDelete($document, $user->name);
                AuditLogger::log($id, $user->id, 'Delete', "Document '{$title}' deleted");
                
                // Soft delete the document
                $document->update(['current_status' => 'deleted']);
                $document->delete();
            });
            
            return response()->json(['message' => 'Document deleted successfully']);
        } catch (\Exception $e) {
            \Log::error('Error deleting document: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to delete document: ' . $e->getMessage()], 500);
        }
    }
}

