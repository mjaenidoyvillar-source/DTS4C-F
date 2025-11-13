<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BugReport;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class BugReportController extends Controller
{
    public function index(Request $request)
    {
        // Only allow admin role
        $user = auth()->user();
        if (!$user || $user->role !== 'admin') {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['message' => 'Unauthorized access to bug reports'], 403);
            }
            abort(403, 'Unauthorized access to bug reports');
        }

        try {
            $query = BugReport::with(['user', 'resolvedBy']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Filter by severity
        if ($request->filled('severity')) {
            $query->where('severity', $request->input('severity'));
        }

        // Filter by user
        if ($request->filled('user_id')) {
            $query->where('user_id', (int) $request->input('user_id'));
        }

        // Filter by error type
        if ($request->filled('error_type')) {
            $query->where('error_type', 'like', '%' . $request->input('error_type') . '%');
        }

        $bugReports = $query->latest()->paginate(20);

        $users = User::orderBy('email')->get();
        $statuses = ['open', 'resolved', 'ignored'];
        $severities = ['critical', 'error', 'warning'];

        // Return JSON for API requests
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'data' => $bugReports->map(function($report) {
                    return [
                        'id' => $report->id,
                        'error_type' => $report->error_type,
                        'severity' => $report->severity,
                        'message' => $report->message,
                        'file' => $report->file,
                        'line' => $report->line,
                        'url' => $report->url,
                        'method' => $report->method,
                        'user' => $report->user ? ($report->user->name ?? $report->user->email ?? 'Unknown') : 'System',
                        'status' => $report->status,
                        'occurrence_count' => $report->occurrence_count,
                        'created_at' => $report->created_at->format('Y-m-d H:i:s'),
                        'resolved_at' => $report->resolved_at?->format('Y-m-d H:i:s'),
                    ];
                }),
                'current_page' => $bugReports->currentPage(),
                'last_page' => $bugReports->lastPage(),
                'per_page' => $bugReports->perPage(),
                'total' => $bugReports->total(),
                'filters' => [
                    'users' => $users->map(fn($u) => ['id' => $u->id, 'name' => $u->name ?? $u->email, 'email' => $u->email]),
                    'statuses' => $statuses,
                    'severities' => $severities,
                ],
            ]);
        }

        return view('admin.bug_reports', [
            'bugReports' => $bugReports,
            'users' => $users,
            'statuses' => $statuses,
            'severities' => $severities,
        ]);
        } catch (\Exception $e) {
            Log::error('Bug Report Index Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => 'An error occurred while loading bug reports',
                    'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
                ], 500);
            }
            
            return redirect()->route('dashboard')
                ->with('error', 'An error occurred while loading bug reports. Please check the logs.');
        }
    }

    public function show(Request $request, BugReport $bugReport)
    {
        $user = auth()->user();
        if (!$user || $user->role !== 'admin') {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
            abort(403);
        }

        $bugReport->load(['user', 'resolvedBy']);

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'id' => $bugReport->id,
                'error_type' => $bugReport->error_type,
                'severity' => $bugReport->severity,
                'message' => $bugReport->message,
                'file' => $bugReport->file,
                'line' => $bugReport->line,
                'trace' => $bugReport->trace,
                'url' => $bugReport->url,
                'method' => $bugReport->method,
                'user' => $bugReport->user ? [
                    'id' => $bugReport->user->id,
                    'name' => $bugReport->user->name ?? $bugReport->user->email,
                    'email' => $bugReport->user->email,
                ] : null,
                'ip_address' => $bugReport->ip_address,
                'user_agent' => $bugReport->user_agent,
                'request_data' => $bugReport->request_data,
                'status' => $bugReport->status,
                'resolution_notes' => $bugReport->resolution_notes,
                'resolved_by' => $bugReport->resolvedBy ? [
                    'id' => $bugReport->resolvedBy->id,
                    'name' => $bugReport->resolvedBy->name ?? $bugReport->resolvedBy->email,
                ] : null,
                'resolved_at' => $bugReport->resolved_at?->format('Y-m-d H:i:s'),
                'occurrence_count' => $bugReport->occurrence_count,
                'created_at' => $bugReport->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $bugReport->updated_at->format('Y-m-d H:i:s'),
            ]);
        }

        return view('admin.bug_report_detail', compact('bugReport'));
    }

    public function updateStatus(Request $request, BugReport $bugReport)
    {
        $user = auth()->user();
        if (!$user || $user->role !== 'admin') {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
            abort(403);
        }

        $data = $request->validate([
            'status' => 'required|in:open,resolved,ignored',
            'resolution_notes' => 'nullable|string|max:1000',
        ]);

        $bugReport->status = $data['status'];
        if (isset($data['resolution_notes'])) {
            $bugReport->resolution_notes = $data['resolution_notes'];
        }

        if ($data['status'] === 'resolved' || $data['status'] === 'ignored') {
            $bugReport->resolved_by = $user->id;
            $bugReport->resolved_at = now();
        } else {
            $bugReport->resolved_by = null;
            $bugReport->resolved_at = null;
        }

        $bugReport->save();

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'message' => 'Bug report status updated successfully',
                'bug_report' => $bugReport->fresh(['resolvedBy']),
            ]);
        }

        return redirect()->route('admin.bug-reports.index')->with('status', 'Bug report status updated');
    }

    public function destroy(Request $request, BugReport $bugReport)
    {
        $user = auth()->user();
        if (!$user || $user->role !== 'admin') {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
            abort(403);
        }

        $bugReport->delete();

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'message' => 'Bug report deleted successfully',
            ]);
        }

        return redirect()->route('admin.bug-reports.index')->with('status', 'Bug report deleted');
    }
}
