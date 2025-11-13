<?php

namespace App\Http\Controllers\Handler;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HandlesApiRequests;
use App\Models\Document;
use App\Models\DocumentRoute;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\NotificationService;
use App\Services\DocumentLogService;
use App\Mail\DocumentReceivedMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class DocumentController extends Controller
{
    use HandlesApiRequests;

    protected $notificationService;
    protected $documentLogService;

    public function __construct(
        NotificationService $notificationService,
        DocumentLogService $documentLogService
    ) {
        $this->notificationService = $notificationService;
        $this->documentLogService = $documentLogService;
    }
    public function myDocuments(Request $request)
    {
        $user = Auth::user();
        if (($user->role ?? null) !== 'handler' || !$user->department_id) {
            if ($this->isApiRequest($request)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
            abort(403);
        }
        // Show documents from handler's department
        // EXCLUDE: Same-dept owner-to-owner documents (they bypass handler completely)
        $documents = Document::where('department_id', $user->department_id)
            // Exclude same-dept owner-to-owner documents (current_handler_id is null and status is pending_recipient_owner)
            ->where(function($q) {
                $q->whereNotNull('current_handler_id')
                  ->orWhere(function($q2) {
                      $q2->whereColumn('department_id', '!=', 'receiving_department_id')
                         ->orWhere('current_status', '!=', 'pending_recipient_owner');
                  });
            })
            ->orderByDesc('created_at')
            ->paginate(15);

        if ($this->isApiRequest($request)) {
            return response()->json($this->formatPaginatedDocuments($documents));
        }

        return view('handler.my_documents', [
            'user' => $user,
            'documents' => $documents,
        ]);
    }

    public function send(Request $request, Document $document)
    {
        try {
            \Log::info('Send method called', [
                'document_id' => $document->id,
                'user_id' => Auth::id(),
                'request_path' => $request->path(),
            ]);
            $user = Auth::user();
            
            if (!$user) {
                \Log::error('No authenticated user');
                return response()->json(['message' => 'Unauthenticated'], 401);
            }
            
            if (($user->role ?? null) !== 'handler') {
                \Log::warning('User is not a handler', ['user_role' => $user->role ?? 'null']);
                return response()->json(['message' => 'Only handlers can send documents'], 403);
            }
            
            // CRITICAL: Handler must belong to the document's department (creator's department)
            // This ensures the document is first handled by the handler from the creator's department
            if ($user->department_id !== $document->department_id) {
                \Log::warning('Handler department mismatch', [
                    'user_dept' => $user->department_id,
                    'doc_dept' => $document->department_id
                ]);
                return response()->json(['message' => 'Only the handler from the document creator\'s department can send this document'], 403);
            }
            
            // Validate document has receiving department and target owner specified by creator
            if (!$document->receiving_department_id || !$document->target_owner_id) {
                \Log::warning('Document missing required fields', [
                    'receiving_dept_id' => $document->receiving_department_id,
                    'target_owner_id' => $document->target_owner_id
                ]);
                return response()->json(['message' => 'Document must have receiving department and target owner specified by creator'], 422);
            }
            
            // If same department, document should already be sent directly to owner (bypass handler)
            if ($document->receiving_department_id == $document->department_id) {
                return response()->json(['message' => 'Documents to the same department are sent directly to the owner and do not need handler processing'], 422);
            }

            $targetDeptId = $document->receiving_department_id;
            $targetOwnerId = $document->target_owner_id;
            
            // Verify target owner is still valid
            $targetOwner = User::find($targetOwnerId);
            if (!$targetOwner || $targetOwner->department_id != $targetDeptId || $targetOwner->role !== 'owner') {
                return response()->json(['message' => 'Invalid target owner specified'], 422);
            }

            DB::beginTransaction();
            try {
                // Validate all required IDs are present
                if (!$user->department_id) {
                    DB::rollBack();
                    return response()->json(['message' => 'Your account is not assigned to a department'], 422);
                }
                
                // Find handler in target department
                $targetHandler = User::where('department_id', $targetDeptId)
                    ->where('role', 'handler')
                    ->where('is_active', true)
                    ->first();
                
                if (!$targetHandler) {
                    DB::rollBack();
                    \Log::warning('No active handler found in target department', [
                        'target_dept_id' => $targetDeptId
                    ]);
                    return response()->json(['message' => 'No active handler found in target department'], 422);
                }
                
                \Log::info('Creating document route', [
                    'document_id' => $document->id,
                    'from_dept' => $user->department_id,
                    'to_dept' => $targetDeptId,
                    'target_handler_id' => $targetHandler->id
                ]);
                
                $route = DocumentRoute::create([
                    'document_id' => $document->id,
                    'from_department_id' => $user->department_id,
                    'to_department_id' => $targetDeptId,
                    'from_user_id' => $user->id,
                    'to_user_id' => $targetHandler->id,
                    'target_owner_id' => $targetOwnerId,
                    'new_status' => 'Sent',
                ]);

                \Log::info('Updating document', [
                    'document_id' => $document->id,
                    'new_status' => 'pending_recipient_handler',
                    'new_handler_id' => $targetHandler->id
                ]);

                $document->current_status = 'pending_recipient_handler';
                $document->current_handler_id = $targetHandler->id;
                $document->save();

                // Log and notify (wrap in try-catch to prevent transaction failure)
                try {
                    $this->documentLogService->logSend($document, $user, "Handler {$user->name} sent document to department {$targetDeptId} handler, target owner: {$targetOwner->name}.");
                } catch (\Exception $logError) {
                    \Log::warning('Failed to log send action: ' . $logError->getMessage());
                }
                
                try {
                    $this->notificationService->notifyRecipientHandler($document, $targetHandler->id);
                } catch (\Exception $notifError) {
                    \Log::warning('Failed to send notification: ' . $notifError->getMessage());
                }
                
                try {
                    AuditLogger::log($document->id, $user->id, 'Send', "Handler {$user->name} sent document to department {$targetDeptId} handler, target owner: {$targetOwner->name}.");
                } catch (\Exception $auditError) {
                    \Log::warning('Failed to log audit: ' . $auditError->getMessage());
                }
                
                DB::commit();
            } catch (\Illuminate\Database\QueryException $e) {
                DB::rollBack();
                \Log::error('Database error sending document: ' . $e->getMessage(), [
                    'document_id' => $document->id,
                    'user_id' => $user->id,
                    'sql' => $e->getSql(),
                    'bindings' => $e->getBindings(),
                ]);
                return response()->json([
                    'message' => 'Database error while sending document',
                    'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while sending the document'
                ], 500);
            } catch (\Exception $e) {
                DB::rollBack();
                \Log::error('Error sending document: ' . $e->getMessage(), [
                    'document_id' => $document->id,
                    'user_id' => $user->id,
                    'trace' => $e->getTraceAsString()
                ]);
                return response()->json([
                    'message' => 'Failed to send document',
                    'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while sending the document'
                ], 500);
            }

            // Refresh document and load relationships
            $document->refresh();
            $document->load(['department', 'owner', 'currentHandler', 'receivingDepartment', 'targetOwner']);
            
            if ($this->isApiRequest($request)) {
                try {
                    $formattedDocument = $this->formatDocumentForApi($document);
                    return response()->json([
                        'message' => 'Document sent successfully',
                        'document' => $formattedDocument,
                    ], 200);
                } catch (\Exception $formatError) {
                    \Log::error('Error formatting document for API: ' . $formatError->getMessage(), [
                        'document_id' => $document->id,
                        'trace' => $formatError->getTraceAsString()
                    ]);
                    // Return success even if formatting fails, but include error in response
                    return response()->json([
                        'message' => 'Document sent successfully',
                        'document_id' => $document->id,
                        'current_status' => $document->current_status,
                        'current_handler_id' => $document->current_handler_id,
                        'warning' => 'Error formatting full document details',
                        'error' => config('app.debug') ? $formatError->getMessage() : null
                    ], 200);
                }
            }
            return back()->with('status', 'Document sent');
        } catch (\Exception $e) {
            \Log::error('Error in send method: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'message' => 'An error occurred',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    public function receive(Request $request, $documentOrRoute)
    {
        $user = Auth::user();
        if (($user->role ?? null) !== 'handler') {
            return response()->json(['message' => 'Only handlers can receive documents'], 403);
        }
        
        // Handle both Document and DocumentRoute parameters
        $document = null;
        $documentRoute = null;
        
        if ($documentOrRoute instanceof Document) {
            $document = $documentOrRoute;
            // Find the route for this document sent to this handler
            $documentRoute = DocumentRoute::where('document_id', $document->id)
                ->where('to_department_id', $user->department_id)
                ->where(function($q) use ($user) {
                    // Allow either a specific assignment to this handler OR department-level assignment
                    $q->where('to_user_id', $user->id)->orWhereNull('to_user_id');
                })
                ->where(function($q) {
                    $q->whereNull('new_status')->orWhere('new_status', 'Sent');
                })
                ->first();
            
            if (!$documentRoute) {
                return response()->json(['message' => 'No pending route found for this document'], 404);
            }
        } elseif ($documentOrRoute instanceof DocumentRoute) {
            $documentRoute = $documentOrRoute;
            $document = $documentRoute->document;
        } else {
            // Try to find by ID - could be document ID or route ID
            $documentRoute = DocumentRoute::where('id', $documentOrRoute)
                ->where('to_department_id', $user->department_id)
                ->where(function($q) use ($user) {
                    $q->where('to_user_id', $user->id)->orWhereNull('to_user_id');
                })
                ->first();
            
            if ($documentRoute) {
                $document = $documentRoute->document;
            } else {
                // Try as document ID
                $document = Document::find($documentOrRoute);
                if ($document) {
                    $documentRoute = DocumentRoute::where('document_id', $document->id)
                        ->where('to_department_id', $user->department_id)
                        ->where(function($q) use ($user) {
                            $q->where('to_user_id', $user->id)->orWhereNull('to_user_id');
                        })
                        ->where(function($q) {
                            $q->whereNull('new_status')->orWhere('new_status', 'Sent');
                        })
                        ->first();
                    
                    if (!$documentRoute) {
                        return response()->json(['message' => 'No pending route found for this document'], 404);
                    }
                } else {
                    return response()->json(['message' => 'Document or route not found'], 404);
                }
            }
        }
        
        if ($user->department_id !== $documentRoute->to_department_id) {
            return response()->json(['message' => 'Handler must belong to the target department'], 403);
        }

        DB::transaction(function () use ($user, $documentRoute, $document) {
            $documentRoute->update(['new_status' => 'Received']);

            // Preserve the original owner_id and department_id - they should never change
            // department_id always represents the sender's department (owner's department)
            $document->update([
                'current_status' => 'received_by_handler',
                // department_id is NOT updated - it remains as the sender's department
                // If the route was department-level (no specific handler), set current handler to receiving user
                // Otherwise keep current handler as assigned
                'current_handler_id' => $user->id,
                // owner_id is NOT updated - it remains with the original creator
            ]);

            $this->documentLogService->logReceive($document, $user, "Handler {$user->name} in dept {$user->department_id} received document for processing.");
            
            // Send email notification to document owner about receipt
            try {
                // Load owner relationship if not loaded
                if (!$document->relationLoaded('owner')) {
                    $document->load('owner');
                }
                if ($document->owner && $document->owner->email) {
                    \Log::info('Sending received email to document owner', [
                        'owner_email' => $document->owner->email,
                        'document_title' => $document->title,
                        'receiver_name' => $user->name
                    ]);
                    Mail::to($document->owner->email)->send(new DocumentReceivedMail($document->title, $user->name));
                    \Log::info('Received email sent successfully', ['email' => $document->owner->email]);
                } else {
                    \Log::warning('Document owner not found or has no email', [
                        'owner_id' => $document->owner_id,
                        'has_email' => $document->owner ? ($document->owner->email ? 'yes' : 'no') : 'owner_not_found'
                    ]);
                }
            } catch (\Exception $e) {
                \Log::error('Failed to send document received email: ' . $e->getMessage(), [
                    'document_id' => $document->id,
                    'owner_id' => $document->owner_id,
                    'trace' => $e->getTraceAsString()
                ]);
            }
            
            AuditLogger::log($document->id, $user->id, 'Receive', "Handler {$user->name} in dept {$user->department_id} received document for processing.");
        });

        if ($this->isApiRequest($request)) {
            return response()->json([
                'message' => 'Document received successfully',
                'document' => $this->formatDocumentForApi($document->fresh()),
            ]);
        }
        return back()->with('status', 'Document received');
    }

    public function forwardToOwner(Request $request, Document $document)
    {
        $user = Auth::user();
        if (($user->role ?? null) !== 'handler') {
            return response()->json(['message' => 'Only handlers can forward to owner'], 403);
        }
        
        // Find the incoming route
        $incomingRoute = DocumentRoute::where('document_id', $document->id)
            ->where('to_department_id', $user->department_id)
            ->where(function($q) {
                $q->whereNull('new_status')->orWhere('new_status', '!=', 'Received');
            })
            ->latest()
            ->first();

        // Use target_owner_id from the document (set by original creator)
        // This is the correct owner specified when the document was created
        $targetOwner = null;
        if ($document->target_owner_id) {
            $targetOwner = User::find($document->target_owner_id);
            // Verify the target owner is in the handler's department and is an owner
            if (!$targetOwner || $targetOwner->department_id != $user->department_id || $targetOwner->role !== 'owner') {
                $targetOwner = null; // Invalid target owner, fall back to route or default
            }
        }
        
        // Fallback: try to get from incoming route if document doesn't have it
        if (!$targetOwner && $incomingRoute && $incomingRoute->target_owner_id) {
            $targetOwner = User::find($incomingRoute->target_owner_id);
            if (!$targetOwner || $targetOwner->department_id != $user->department_id || $targetOwner->role !== 'owner') {
                $targetOwner = null;
            }
        }
        
        // Final fallback: first owner in department
        if (!$targetOwner) {
            $targetOwner = User::where('department_id', $user->department_id)
                ->where('role', 'owner')
                ->first();
        }
        
        if (!$targetOwner) {
            return response()->json(['message' => 'No owner found in your department'], 404);
        }

        $isCurrentlyHandling = ($document->current_handler_id === $user->id);
        $hasIncomingRoute = $incomingRoute !== null;

        if (!$isCurrentlyHandling && !$hasIncomingRoute) {
            return response()->json(['message' => 'You can only forward documents you are currently handling or have received'], 403);
        }

        DB::transaction(function () use ($user, $document, $targetOwner, $incomingRoute) {
            if ($incomingRoute) {
                // Mark the incoming route as received (if it was pending)
                $incomingRoute->update(['new_status' => 'Received']);
                
                // Update document status - department_id should NOT be updated (it's the sender's department)
                $document->update([
                    // department_id is NOT updated - it remains as the sender's department
                    'current_handler_id' => $user->id,
                    'current_status' => 'In Progress',
                ]);
            }

            DocumentRoute::create([
                'document_id' => $document->id,
                'from_department_id' => $user->department_id,
                'to_department_id' => $user->department_id,
                'from_user_id' => $user->id,
                'to_user_id' => $targetOwner->id,
                'target_owner_id' => $targetOwner->id,
                'new_status' => 'For Review',
            ]);

            // Preserve the original owner_id and department_id - they should never change
            // department_id always represents the sender's department (owner's department)
            $document->update([
                // department_id is NOT updated - it remains as the sender's department
                'current_handler_id' => null,
                'current_owner_id' => $targetOwner->id,
                'current_status' => 'pending_recipient_owner',
                // owner_id is NOT updated - it remains with the original creator
            ]);

            $this->documentLogService->log($document->id, $user->id, $user->department_id, 'forward', "Handler {$user->name} forwarded document to owner {$targetOwner->name} in department.");
            $this->notificationService->notifyRecipientOwner($document, $targetOwner->id);
            AuditLogger::log($document->id, $user->id, 'Forward', "Handler {$user->name} forwarded document to owner {$targetOwner->name} in department.");
        });

        if ($this->isApiRequest($request)) {
            return response()->json([
                'message' => 'Document forwarded to owner successfully',
                'document' => $this->formatDocumentForApi($document->fresh()),
            ]);
        }
        return back()->with('status', 'Document forwarded to owner');
    }

    /**
     * Reject a document and return it to the sender owner
     */
    public function reject(Request $request, Document $document)
    {
        $user = Auth::user();
        if (($user->role ?? null) !== 'handler') {
            return response()->json(['message' => 'Only handlers can reject documents'], 403);
        }

        // Handler can reject if they are the current handler
        if ($document->current_handler_id !== $user->id) {
            return response()->json(['message' => 'You can only reject documents assigned to you'], 403);
        }

        $reason = $request->input('reason', 'No reason provided');

        DB::transaction(function () use ($user, $document, $reason) {
            // Get the original owner (sender)
            $originalOwner = $document->owner;
            
            if (!$originalOwner) {
                throw new \Exception('Original document owner not found');
            }

            // Create a route to track the rejection/return
            DocumentRoute::create([
                'document_id' => $document->id,
                'from_department_id' => $user->department_id,
                'to_department_id' => $document->department_id, // Return to sender's department
                'from_user_id' => $user->id,
                'to_user_id' => $originalOwner->id,
                'target_owner_id' => $originalOwner->id,
                'new_status' => 'Rejected',
            ]);

            // Return document to the original owner
            $document->update([
                'current_status' => 'rejected',
                'current_handler_id' => null,
                'current_owner_id' => $originalOwner->id, // Return to original owner
            ]);

            $this->documentLogService->logReject($document, $user, $reason);
            $this->notificationService->notifyRejection($document, $reason, $user);
            AuditLogger::log($document->id, $user->id, 'Reject', "Handler {$user->name} rejected document '{$document->title}' and returned it to owner {$originalOwner->name}. Reason: {$reason}");
        });

        return response()->json([
            'message' => 'Document rejected and returned to sender successfully',
            'document' => $this->formatDocumentForApi($document->fresh()),
        ]);
    }

    public function sendToOwner(Request $request, Document $document)
    {
        $user = Auth::user();
        if (($user->role ?? null) !== 'handler') {
            return response()->json(['message' => 'Only handlers can send documents to owners'], 403);
        }
        
        // Handler must belong to the document's department
        if ($user->department_id !== $document->department_id) {
            return response()->json(['message' => 'Only the handler from the document\'s department can send to owners'], 403);
        }
        
        $data = $request->validate([
            'owner_id' => 'required|exists:users,id',
        ]);
        
        $targetOwner = User::find($data['owner_id']);
        
        // Validate that target owner is in the same department and is an owner
        if (!$targetOwner || $targetOwner->department_id !== $user->department_id || $targetOwner->role !== 'owner') {
            return response()->json(['message' => 'Target owner must be an owner in your department'], 422);
        }
        
        // Check if document can be sent (not already completed or archived)
        if (in_array($document->current_status, ['Completed', 'Archived'])) {
            return response()->json(['message' => 'Cannot send document that is completed or archived'], 422);
        }
        
        DB::transaction(function () use ($user, $document, $targetOwner) {
            // Create route to the owner
            DocumentRoute::create([
                'document_id' => $document->id,
                'from_department_id' => $user->department_id,
                'to_department_id' => $user->department_id,
                'from_user_id' => $user->id,
                'to_user_id' => $targetOwner->id,
                'target_owner_id' => $targetOwner->id,
                'new_status' => 'For Review',
            ]);
            
            // Update document status
            $document->update([
                'current_handler_id' => $targetOwner->id,
                'current_status' => 'For Review',
            ]);
            
            AuditLogger::log($document->id, $user->id, 'Send To Owner', "Handler {$user->name} sent document directly to owner {$targetOwner->name} in the same department.");
        });
        
        if ($this->isApiRequest($request)) {
            return response()->json([
                'message' => 'Document sent to owner successfully',
                'document' => $this->formatDocumentForApi($document->fresh()),
            ]);
        }
        return back()->with('status', 'Document sent to owner');
    }

    public function hold(Request $request, Document $document)
    {
        $user = Auth::user();
        if (($user->role ?? null) !== 'handler' || !$user->department_id) {
            return response()->json(['message' => 'Only handlers can hold documents'], 403);
        }
        
        // Handler can hold if:
        // 1. They are the current handler assigned to the document, OR
        // 2. They are in the document's current department (after it's been received), OR
        // 3. There's a route pending receipt to their department (document was sent to them)
        $isCurrentHandler = ($document->current_handler_id === $user->id);
        $isInCurrentDepartment = ($user->department_id === $document->department_id);
        $hasPendingRoute = DocumentRoute::where('document_id', $document->id)
            ->where('to_department_id', $user->department_id)
            ->where(function($q) {
                $q->whereNull('new_status')->orWhere('new_status', 'Sent');
            })
            ->exists();
        
        $canHold = $isCurrentHandler || $isInCurrentDepartment || $hasPendingRoute;
        
        if (!$canHold) {
            return response()->json(['message' => 'You can only hold documents that are assigned to you, in your department, or pending receipt in your department'], 403);
        }
        
        $reason = $request->input('reason');
        
        // If document hasn't been received yet, set up the handler assignment
        if (!$isCurrentHandler) {
            $document->update([
                'current_status' => 'On Hold',
                'current_handler_id' => $user->id,
                // department_id is NOT updated - it remains as the sender's department
            ]);
        } else {
            $document->update(['current_status' => 'On Hold', 'current_handler_id' => $user->id]);
        }
        
        // Mark any pending routes as received if they exist
        DocumentRoute::where('document_id', $document->id)
            ->where('to_department_id', $user->department_id)
            ->where(function($q) {
                $q->whereNull('new_status')->orWhere('new_status', 'Sent');
            })
            ->update(['new_status' => 'Received']);
        
        AuditLogger::log($document->id, $user->id, 'Hold', $reason ? "On Hold: $reason" : 'On Hold');
        
        if ($this->isApiRequest($request)) {
            return response()->json([
                'message' => 'Document placed on hold',
                'document' => $this->formatDocumentForApi($document->fresh()),
            ]);
        }
        return back()->with('status', 'Document placed on hold');
    }

    public function resume(Request $request, Document $document)
    {
        $user = Auth::user();
        if (($user->role ?? null) !== 'handler') {
            return response()->json(['message' => 'Only handlers can resume documents'], 403);
        }
        
        // Handler can resume if they are the current handler
        if ($document->current_handler_id !== $user->id) {
            return response()->json(['message' => 'You can only resume documents that are on hold and assigned to you'], 403);
        }
        
        // Only resume if document is actually on hold
        if ($document->current_status !== 'On Hold') {
            return response()->json(['message' => 'Document is not on hold'], 422);
        }
        
        $document->update(['current_status' => 'In Progress', 'current_handler_id' => $user->id]);
        AuditLogger::log($document->id, $user->id, 'Resume', 'Resumed from hold');
        
        if ($this->isApiRequest($request)) {
            return response()->json([
                'message' => 'Document resumed',
                'document' => $this->formatDocumentForApi($document->fresh()),
            ]);
        }
        return back()->with('status', 'Document resumed');
    }

    public function incoming(Request $request)
    {
        $user = Auth::user();
        if (($user->role ?? null) !== 'handler' || !$user->department_id) {
            if ($this->isApiRequest($request)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
            abort(403);
        }
        $routes = DocumentRoute::with('document', 'fromDepartment', 'targetOwner')
            ->where('to_department_id', $user->department_id)
            ->where(function($q){ $q->whereNull('new_status')->orWhere('new_status','!=','Received'); })
            ->orderByDesc('created_at')
            ->paginate(15);

        if ($this->isApiRequest($request)) {
            return response()->json([
                'data' => $routes->map(function($route) {
                    return [
                        'id' => $route->id,
                        'document' => $this->formatDocumentForApi($route->document),
                        'from_department' => $route->fromDepartment->name ?? null,
                        'from_department_id' => $route->from_department_id,
                        'to_department_id' => $route->to_department_id,
                        'target_owner' => $route->targetOwner->name ?? $route->targetOwner->email ?? null,
                        'target_owner_id' => $route->target_owner_id,
                        'status' => $route->new_status ?? 'Sent',
                        'created_at' => $route->created_at->format('Y-m-d H:i:s'),
                    ];
                }),
                'current_page' => $routes->currentPage(),
                'last_page' => $routes->lastPage(),
                'per_page' => $routes->perPage(),
                'total' => $routes->total(),
            ]);
        }

        return view('handler.incoming', compact('user', 'routes'));
    }

    public function toProcess(Request $request)
    {
        $user = Auth::user();
        if (($user->role ?? null) !== 'handler' || !$user->department_id) {
            if ($this->isApiRequest($request)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
            abort(403);
        }
        // Get documents that are assigned to this handler or received by this handler
        // Exclude documents meant for same department (they bypass handler and go directly to owner)
        // EXCLUDE: Documents sent between owners in same department (bypass handler, current_handler_id is null)
        $documents = Document::with(['owner', 'routes'])
            ->where(function($q) use ($user) {
                // Documents currently assigned to this handler (including pending_recipient_handler status)
                $q->where(function($q2) use ($user) {
                    $q2->where('current_handler_id', $user->id)
                       ->whereIn('current_status', [
                           'Registered', 
                           'In Progress', 
                           'pending_recipient_handler', // Documents sent to this handler
                           'received_by_handler' // Documents received by this handler
                       ])
                       ->where(function($sameDeptCheck) use ($user) {
                           // Exclude documents meant for same department (they should bypass handler)
                           $sameDeptCheck->where('receiving_department_id', '!=', $user->department_id)
                                        ->orWhereNull('receiving_department_id');
                       })
                       // Explicitly exclude same-dept owner-to-owner documents (status is pending_recipient_owner and same department)
                       ->where(function($excludeSameDept) {
                           $excludeSameDept->whereColumn('department_id', '!=', 'receiving_department_id')
                                           ->orWhere('current_status', '!=', 'pending_recipient_owner');
                       });
                })
                // OR documents received by this handler from another department (ready to forward to owner)
                ->orWhere(function($q3) use ($user) {
                    $q3->where('current_handler_id', $user->id)
                       ->where('current_status', 'received_by_handler')
                       ->whereHas('routes', function($routeQuery) use ($user) {
                           $routeQuery->where('from_department_id', '!=', $user->department_id) // FROM another department
                                     ->where('to_department_id', $user->department_id) // TO this handler's department
                                     ->where('new_status', 'Received');
                       });
                })
                // OR documents pending receipt (sent to this department but not yet received)
                ->orWhere(function($q5) use ($user) {
                    $q5->where('current_handler_id', $user->id)
                       ->where('current_status', 'pending_recipient_handler')
                       ->whereHas('routes', function($routeQuery) use ($user) {
                           $routeQuery->where('from_department_id', '!=', $user->department_id) // FROM another department
                                     ->where('to_department_id', $user->department_id) // TO this handler's department
                                     ->where(function($statusQuery) {
                                         $statusQuery->whereNull('new_status')
                                                     ->orWhere('new_status', 'Sent'); // Pending receipt
                                     });
                       });
                })
                // OR unassigned documents from owner's department (but only if meant for different department)
                ->orWhere(function($q4) use ($user) {
                    $q4->whereNull('current_handler_id')
                       ->where('current_status', 'Registered')
                       ->where('receiving_department_id', '!=', $user->department_id) // Must be for different department
                       ->whereHas('owner', function($ownerQuery) use ($user) {
                           $ownerQuery->where('department_id', $user->department_id);
                       });
                });
            })
            ->orderByDesc('created_at')
            ->paginate(15);
        
        // Mark documents that were received from another department (ready for owner)
        $documents->getCollection()->transform(function($doc) use ($user) {
            // A document is "received" if it has a route FROM another department TO this handler's department with 'Received' status
            $wasReceived = $doc->routes()
                ->where('from_department_id', '!=', $user->department_id) // FROM another department
                ->where('to_department_id', $user->department_id) // TO this handler's department
                ->where('new_status', 'Received')
                ->exists();
            
            // Check if document is pending receipt (sent but not yet received)
            $isPendingReceipt = $doc->routes()
                ->where('from_department_id', '!=', $user->department_id) // FROM another department
                ->where('to_department_id', $user->department_id) // TO this handler's department
                ->where(function($q) {
                    $q->whereNull('new_status')->orWhere('new_status', 'Sent');
                })
                ->exists();
            
            $doc->is_received = $wasReceived;
            $doc->is_pending_receipt = $isPendingReceipt && !$wasReceived; // Only pending if not already received
            return $doc;
        });

        if ($this->isApiRequest($request)) {
            return response()->json([
                'data' => $documents->map(function($doc) {
                    $formatted = $this->formatDocumentForApi($doc);
                    $formatted['is_received'] = $doc->is_received ?? false;
                    $formatted['is_pending_receipt'] = $doc->is_pending_receipt ?? false;
                    return $formatted;
                }),
                'current_page' => $documents->currentPage(),
                'last_page' => $documents->lastPage(),
                'per_page' => $documents->perPage(),
                'total' => $documents->total(),
            ]);
        }
        
        return view('handler.to_process', compact('user', 'documents'));
    }

    public function onHold(Request $request)
    {
        $user = Auth::user();
        if (($user->role ?? null) !== 'handler' || !$user->department_id) {
            if ($this->isApiRequest($request)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
            abort(403);
        }
        // Show documents sent to this handler:
        // 1. Documents sent by department owners from handler's own department (pending_handler_review)
        // 2. Documents sent by handlers from other departments (pending_recipient_handler)
        // 3. Documents received by this handler (received_by_handler) - waiting to forward to owner
        // EXCLUDE: Documents sent between owners in same department (bypass handler, current_handler_id is null)
        $documents = Document::with(['owner', 'receivingDepartment', 'targetOwner', 'department'])
            ->where('current_handler_id', $user->id)
            ->whereIn('current_status', ['pending_handler_review', 'pending_recipient_handler', 'received_by_handler'])
            // Exclude same-department owner-to-owner documents (they bypass handler completely)
            ->where(function($q) use ($user) {
                // Exclude if it's a same-dept owner-to-owner document (department_id = receiving_department_id and status is pending_recipient_owner)
                $q->where(function($q2) use ($user) {
                    $q2->where('department_id', '!=', 'receiving_department_id')
                       ->orWhere('current_status', '!=', 'pending_recipient_owner')
                       ->orWhere('receiving_department_id', '!=', $user->department_id);
                });
            })
            ->orderByDesc('created_at')
            ->paginate(15);

        if ($this->isApiRequest($request)) {
            return response()->json($this->formatPaginatedDocuments($documents));
        }

        return view('handler.on_hold', compact('user', 'documents'));
    }

    public function outgoing(Request $request)
    {
        $user = Auth::user();
        if (($user->role ?? null) !== 'handler' || !$user->department_id) {
            if ($this->isApiRequest($request)) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
            abort(403);
        }
        
        // Show documents that were sent to another department's handler (outgoing)
        // These are documents where this handler's department sent to another department
        $routes = DocumentRoute::with('document', 'toDepartment', 'targetOwner')
            ->where('from_department_id', $user->department_id)
            ->where('to_department_id', '!=', $user->department_id) // Sent to different department
            ->where('from_user_id', $user->id) // Sent by this handler
            ->orderByDesc('created_at')
            ->paginate(15);

        if ($this->isApiRequest($request)) {
            return response()->json([
                'data' => $routes->map(function($route) {
                    return [
                        'id' => $route->id,
                        'document' => $this->formatDocumentForApi($route->document),
                        'to_department' => $route->toDepartment->name ?? null,
                        'target_owner' => $route->targetOwner->name ?? $route->targetOwner->email ?? null,
                        'status' => $route->new_status ?? 'Sent',
                        'created_at' => $route->created_at->format('Y-m-d H:i:s'),
                    ];
                }),
                'current_page' => $routes->currentPage(),
                'last_page' => $routes->lastPage(),
                'per_page' => $routes->perPage(),
                'total' => $routes->total(),
            ]);
        }

        return view('handler.outgoing', compact('user', 'routes'));
    }
}

