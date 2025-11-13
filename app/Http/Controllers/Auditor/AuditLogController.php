<?php

namespace App\Http\Controllers\Auditor;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        // Only allow admin and auditor roles
        $user = auth()->user();
        if (!$user || !in_array($user->role, ['admin', 'auditor'])) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['message' => 'Unauthorized access to audit logs'], 403);
            }
            abort(403, 'Unauthorized access to audit logs');
        }
        $query = AuditLog::query()->with(['user', 'document']);

        if ($request->filled('department_id')) {
            $deptId = (int) $request->input('department_id');
            $query->whereHas('document', function ($q) use ($deptId) {
                $q->where('department_id', $deptId);
            });
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', (int) $request->input('user_id'));
        }

        if ($request->filled('document_id')) {
            $query->where('document_id', (int) $request->input('document_id'));
        }

        if ($request->filled('action_type')) {
            $query->where('action_type', $request->input('action_type'));
        }

        $logs = $query->latest()->paginate(20);

        $departments = Department::orderBy('name')->get();
        $users = User::orderBy('email')->get();
        $actionTypes = AuditLog::distinct()->pluck('action_type')->filter()->sort()->values();

        // Return JSON for API requests
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'data' => $logs->map(function($log) {
                    $log->load(['user', 'document']);
                    return [
                        'id' => $log->id,
                        'document_id' => $log->document_id,
                        'user_id' => $log->user_id,
                        'user' => $log->user->name ?? $log->user->email ?? 'System',
                        'user_role' => $log->user->role ?? null,
                        'action_type' => $log->action_type,
                        'description' => $log->description,
                        'created_at' => $log->created_at->format('Y-m-d H:i:s'),
                    ];
                }),
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
                'filters' => [
                    'departments' => $departments->map(fn($d) => ['id' => $d->id, 'name' => $d->name]),
                    'users' => $users->map(fn($u) => ['id' => $u->id, 'name' => $u->name ?? $u->email, 'email' => $u->email]),
                    'action_types' => $actionTypes->values(),
                ],
            ]);
        }

        return view('auditor.audit_logs', [
            'logs' => $logs,
            'departments' => $departments,
            'users' => $users,
            'actionTypes' => $actionTypes,
        ]);
    }
    
    /**
     * Activity Logs for Admin - Shows all system activities (login, logout, profile updates, etc.)
     * NOT document-related activities
     */
    public function activityLogs(Request $request)
    {
        // Only allow admin role
        $user = auth()->user();
        if (!$user || $user->role !== 'admin') {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['message' => 'Unauthorized access to activity logs'], 403);
            }
            abort(403, 'Unauthorized access to activity logs');
        }
        
        // Query ActivityLog instead of AuditLog - these are system/user activities, not document activities
        $query = ActivityLog::query()->with(['user']);

        // Filter by user
        if ($request->filled('user_id')) {
            $query->where('user_id', (int) $request->input('user_id'));
        }

        // Filter by action type
        if ($request->filled('action_type')) {
            $query->where('action_type', $request->input('action_type'));
        }

        // Note: Activity logs don't have document_id or department_id
        // They track system activities like login/logout/profile updates

        $logs = $query->latest()->paginate(20);

        $departments = Department::orderBy('name')->get(); // Keep for UI consistency, but won't filter
        $users = User::orderBy('email')->get();
        $actionTypes = ActivityLog::distinct()->pluck('action_type')->filter()->sort()->values();

        return view('admin.activity_logs', [
            'logs' => $logs,
            'departments' => $departments,
            'users' => $users,
            'actionTypes' => $actionTypes,
        ]);
    }

    /**
     * Export audit logs as CSV
     */
    public function exportAuditLogs(Request $request)
    {
        // Only allow admin and auditor roles
        $user = auth()->user();
        if (!$user || !in_array($user->role, ['admin', 'auditor'])) {
            abort(403, 'Unauthorized access to audit logs');
        }

        $query = AuditLog::query()->with(['user', 'document']);

        // Apply same filters as index method
        if ($request->filled('department_id')) {
            $deptId = (int) $request->input('department_id');
            $query->whereHas('document', function ($q) use ($deptId) {
                $q->where('department_id', $deptId);
            });
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', (int) $request->input('user_id'));
        }

        if ($request->filled('document_id')) {
            $query->where('document_id', (int) $request->input('document_id'));
        }

        if ($request->filled('action_type')) {
            $query->where('action_type', $request->input('action_type'));
        }

        $logs = $query->latest()->get();

        $filename = 'audit_logs_' . date('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($logs) {
            $file = fopen('php://output', 'w');
            
            // Add BOM for UTF-8 to ensure Excel displays correctly
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Headers
            fputcsv($file, [
                'ID',
                'Date/Time',
                'User',
                'User Role',
                'Action Type',
                'Description',
                'Document ID',
                'Document Title'
            ]);

            // Data rows
            foreach ($logs as $log) {
                fputcsv($file, [
                    $log->id,
                    $log->created_at->format('Y-m-d H:i:s'),
                    $log->user ? ($log->user->name ?? $log->user->email ?? 'System') : 'System',
                    $log->user->role ?? 'N/A',
                    $log->action_type,
                    $log->description,
                    $log->document_id ?? 'N/A',
                    $log->document->title ?? 'N/A'
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export activity logs as CSV
     */
    public function exportActivityLogs(Request $request)
    {
        // Only allow admin role
        $user = auth()->user();
        if (!$user || $user->role !== 'admin') {
            abort(403, 'Unauthorized access to activity logs');
        }

        $query = ActivityLog::query()->with(['user']);

        // Apply same filters as activityLogs method
        if ($request->filled('user_id')) {
            $query->where('user_id', (int) $request->input('user_id'));
        }

        if ($request->filled('action_type')) {
            $query->where('action_type', $request->input('action_type'));
        }

        $logs = $query->latest()->get();

        $filename = 'activity_logs_' . date('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($logs) {
            $file = fopen('php://output', 'w');
            
            // Add BOM for UTF-8 to ensure Excel displays correctly
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Headers
            fputcsv($file, [
                'ID',
                'Date/Time',
                'User',
                'User Email',
                'Action Type',
                'Description',
                'IP Address',
                'User Agent'
            ]);

            // Data rows
            foreach ($logs as $log) {
                fputcsv($file, [
                    $log->id,
                    $log->created_at->format('Y-m-d H:i:s'),
                    $log->user ? ($log->user->name ?? $log->user->email ?? 'System') : 'System',
                    $log->user->email ?? 'N/A',
                    $log->action_type,
                    $log->description,
                    $log->ip_address ?? 'N/A',
                    $log->user_agent ?? 'N/A'
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}

