<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DocumentLog;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DocumentLogController extends Controller
{
    /**
     * Get all document logs (Admin and Auditor only)
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Only admin and auditor can view all logs
        if (!in_array($user->role, ['admin', 'auditor'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $logs = DocumentLog::with(['document', 'user', 'department'])
            ->orderByDesc('created_at')
            ->paginate(15);

        return response()->json([
            'data' => $logs->map(function($log) {
                return [
                    'id' => $log->id,
                    'document_id' => $log->document_id,
                    'document_title' => $log->document->title ?? null,
                    'user_id' => $log->user_id,
                    'user_name' => $log->user->name ?? $log->user->email ?? null,
                    'department_id' => $log->department_id,
                    'department_name' => $log->department->name ?? null,
                    'action' => $log->action,
                    'remarks' => $log->remarks,
                    'created_at' => $log->created_at->format('Y-m-d H:i:s'),
                ];
            }),
            'current_page' => $logs->currentPage(),
            'last_page' => $logs->lastPage(),
            'per_page' => $logs->perPage(),
            'total' => $logs->total(),
        ]);
    }

    /**
     * Get logs for a specific document
     * 
     * @param Request $request
     * @param int $documentId
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $documentId)
    {
        $user = Auth::user();
        
        // Only admin and auditor can view all logs
        if (!in_array($user->role, ['admin', 'auditor'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $document = Document::findOrFail($documentId);
        
        $logs = DocumentLog::where('document_id', $documentId)
            ->with(['user', 'department'])
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'document' => [
                'id' => $document->id,
                'title' => $document->title,
                'current_status' => $document->current_status,
            ],
            'logs' => $logs->map(function($log) {
                return [
                    'id' => $log->id,
                    'user_id' => $log->user_id,
                    'user_name' => $log->user->name ?? $log->user->email ?? null,
                    'department_id' => $log->department_id,
                    'department_name' => $log->department->name ?? null,
                    'action' => $log->action,
                    'remarks' => $log->remarks,
                    'created_at' => $log->created_at->format('Y-m-d H:i:s'),
                ];
            }),
        ]);
    }
}
