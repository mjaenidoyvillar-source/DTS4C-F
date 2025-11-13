<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\DocumentLog;
use App\Models\DocumentRoute;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Get dashboard data for department owner
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function owner(Request $request)
    {
        $user = Auth::user();
        
        if ($user->role !== 'owner') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Total documents uploaded by the owner
        $totalSent = Document::where('owner_id', $user->id)->count();

        // Total documents received by owner (where current_owner_id is the user)
        $totalReceived = Document::where('current_owner_id', $user->id)
            ->where('current_status', 'received')
            ->count();

        // Documents still under handler or in transit
        $pendingReview = Document::where('owner_id', $user->id)
            ->whereIn('current_status', [
                'pending_handler_review',
                'pending_recipient_handler',
                'received_by_handler',
                'pending_recipient_owner'
            ])
            ->count();

        // Archived documents
        $archivedCount = Document::where('owner_id', $user->id)
            ->where('current_status', 'archived')
            ->count();

        // Rejected documents
        $rejectedCount = Document::where('owner_id', $user->id)
            ->where('current_status', 'rejected')
            ->count();

        // Recent activity (latest 5 document actions from logs)
        $recentActivity = DocumentLog::whereHas('document', function($query) use ($user) {
                $query->where('owner_id', $user->id);
            })
            ->with(['document', 'user', 'department'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(function($log) {
                return [
                    'action' => $log->action,
                    'remarks' => $log->remarks,
                    'document_title' => $log->document->title ?? null,
                    'user_name' => $log->user->name ?? $log->user->email ?? null,
                    'department_name' => $log->department->name ?? null,
                    'created_at' => $log->created_at->format('Y-m-d H:i:s'),
                ];
            });

        return response()->json([
            'total_sent' => $totalSent,
            'total_received' => $totalReceived,
            'pending_review' => $pendingReview,
            'archived_count' => $archivedCount,
            'rejected_count' => $rejectedCount,
            'recent_activity' => $recentActivity,
        ]);
    }

    /**
     * Get dashboard data for department handler
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handler(Request $request)
    {
        $user = Auth::user();
        
        if ($user->role !== 'handler' || !$user->department_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Total documents assigned to handler
        $totalAssigned = Document::where('current_handler_id', $user->id)->count();

        // Documents awaiting handler action (pending to send)
        $pendingToSend = Document::where('current_handler_id', $user->id)
            ->where('current_status', 'pending_handler_review')
            ->count();

        // Documents received from other departments
        $receivedDocs = Document::where('current_handler_id', $user->id)
            ->where('current_status', 'received_by_handler')
            ->count();

        // Rejected or returned documents
        $rejectedDocs = Document::where('current_handler_id', $user->id)
            ->where('current_status', 'rejected')
            ->count();

        // Documents forwarded to recipient owner
        $forwardedDocs = Document::whereHas('logs', function($query) use ($user) {
                $query->where('user_id', $user->id)
                      ->where('action', 'forward');
            })
            ->count();

        // Recent activity (latest 5 logs of handler-related documents)
        $recentActivity = DocumentLog::where('user_id', $user->id)
            ->with(['document', 'department'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(function($log) {
                return [
                    'action' => $log->action,
                    'remarks' => $log->remarks,
                    'document_title' => $log->document->title ?? null,
                    'department_name' => $log->department->name ?? null,
                    'created_at' => $log->created_at->format('Y-m-d H:i:s'),
                ];
            });

        return response()->json([
            'total_assigned' => $totalAssigned,
            'pending_to_send' => $pendingToSend,
            'received_docs' => $receivedDocs,
            'rejected_docs' => $rejectedDocs,
            'forwarded_docs' => $forwardedDocs,
            'recent_activity' => $recentActivity,
        ]);
    }
}
