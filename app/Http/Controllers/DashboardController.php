<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Department;
use App\Models\Document;
use App\Models\DocumentRoute;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        // Use the admin dashboard layout for all roles with role-based visibility
        $role = $user->role ?? 'handler';
        $allDepartments = Department::orderBy('name')->get();
        
        // For owner dashboard, exclude Administration from receiving department options
        $departments = $allDepartments;
        if ($role === 'owner') {
            $departments = $allDepartments->reject(function($dept) {
                return strtolower($dept->name) === 'administration';
            });
        }
        // Recent documents tailored per role
        if ($role === 'owner') {
            // Show documents they sent (owner_id = user->id) OR documents they received (current_owner_id = user->id OR target_owner_id = user->id)
            // Exclude rejected documents from receiving owners - only show rejected to original sender
            $recentDocuments = Document::with(['owner', 'owner.department', 'department', 'targetOwner', 'receivingDepartment'])
                ->where(function($q) use ($user) {
                    // Documents they sent (can see rejected)
                    $q->where('owner_id', $user->id)
                      // OR documents they received (currently assigned to them or targeted to them)
                      // BUT exclude rejected documents - receiving owners should not see rejected
                      ->orWhere(function($q2) use ($user) {
                          $q2->where(function($q3) use ($user) {
                              $q3->where('current_owner_id', $user->id)
                                 ->orWhere('target_owner_id', $user->id);
                          })
                          ->where('owner_id', '!=', $user->id) // Not the sender
                          ->where('current_status', '!=', 'rejected'); // Exclude rejected
                      });
                })
                ->orderByDesc('created_at')
                ->limit(10)
                ->get();
            // Cap sender-visible status at "received" once the document is with a recipient owner
            $recentDocuments->transform(function($doc) use ($user) {
                $statusLower = is_string($doc->current_status) ? strtolower($doc->current_status) : $doc->current_status;
                $isWithRecipientOwner = $doc->current_owner_id && $doc->current_owner_id !== $user->id;
                if ($isWithRecipientOwner && in_array($statusLower, ['received','archived','completed','deleted'], true)) {
                    $doc->current_status = 'received';
                }
                return $doc;
            });
        } elseif ($role === 'handler' && $user->department_id) {
            // Show created documents (from owners in handler's department)
            // AND received documents (currently assigned to this handler)
            // Exclude ONLY same-dept owner-to-owner documents (sender dept = receiving dept)
            $recentDocuments = Document::with(['owner', 'owner.department', 'department', 'targetOwner', 'receivingDepartment'])
                ->where(function($q) use ($user) {
                    // Created documents: from owners in handler's department
                    $q->whereHas('owner', function($ownerQuery) use ($user) {
                            $ownerQuery->where('department_id', $user->department_id);
                        })
                        // OR Received documents: currently assigned to this handler
                        ->orWhere('current_handler_id', $user->id);
                })
                ->orderByDesc('created_at')
                ->limit(20)
                ->get()
                // Filter out same-dept owner-to-owner documents in code
                ->filter(function($doc) {
                    // Exclude if owner's department matches receiving department (same-dept owner-to-owner)
                    if ($doc->owner && $doc->receiving_department_id) {
                        return $doc->owner->department_id != $doc->receiving_department_id;
                    }
                    return true; // Include if no receiving department or no owner
                })
                ->take(10);
        } else {
            // Admin and Auditor: Show all recent documents with owner information
            $recentDocuments = Document::with('owner')->orderByDesc('created_at')->limit(10)->get();
        }
        $pendingRoutes = collect();
        if ($user->department_id) {
            $pendingRoutes = DocumentRoute::with('document')
                ->where('to_department_id', $user->department_id)
                ->where(function($q){ $q->whereNull('new_status')->orWhere('new_status','!=','Received'); })
                ->orderByDesc('created_at')
                ->limit(10)
                ->get();
        }

        // Aggregate stats per role
        $stats = [
            'documents' => 0,
            'auditLogs' => 0,
            'manageUsers' => 0,
            'myDocuments' => 0,
            'forReview' => 0,
            'sent' => 0,
            'completed' => 0,
            'incoming' => 0,
            'outgoing' => 0,
            'toProcess' => 0,
            'received' => 0,
            'onHold' => 0,
        ];

        if ($role === 'admin') {
            $stats['documents'] = Document::count();
            $stats['auditLogs'] = \App\Models\AuditLog::count();
            $stats['manageUsers'] = \App\Models\User::count();
        } elseif ($role === 'owner') {
            // Total documents sent by owner
            $stats['totalSent'] = Document::where('owner_id', $user->id)->count();
            // Total documents received by owner
            $stats['totalReceived'] = Document::where(function($q) use ($user) {
                $q->where('current_owner_id', $user->id)
                  ->orWhere('target_owner_id', $user->id);
            })->where('current_status', 'received')->count();
            // Incoming documents (pending review)
            $stats['incoming'] = Document::where(function($q) use ($user) {
                $q->where('current_owner_id', $user->id)
                  ->orWhere('target_owner_id', $user->id);
            })->where('current_status', 'pending_recipient_owner')->count();
            // Archived documents
            $stats['archived'] = Document::where(function($q) use ($user) {
                $q->where('owner_id', $user->id)
                  ->orWhere('current_owner_id', $user->id);
            })->where('current_status', 'archived')->count();
            // Rejected documents
            $stats['rejected'] = Document::where('owner_id', $user->id)
                ->where('current_status', 'rejected')->count();
            // Pending review (documents still in workflow)
            $stats['pendingReview'] = Document::where('owner_id', $user->id)
                ->whereIn('current_status', [
                    'pending_handler_review',
                    'pending_recipient_handler',
                    'received_by_handler',
                    'pending_recipient_owner'
                ])->count();
        } elseif ($role === 'handler') {
            $deptId = $user->department_id;
            if ($deptId) {
                // On Hold: Documents sent by department owners to handler (pending_handler_review)
                $stats['onHold'] = Document::where('current_status', 'pending_handler_review')
                    ->where('current_handler_id', $user->id)
                    ->whereHas('owner', function($q) use ($deptId) {
                        $q->where('department_id', $deptId);
                    })
                    ->where(function($q) {
                        $q->whereColumn('department_id', '!=', 'receiving_department_id')
                          ->orWhereNull('receiving_department_id');
                    })
                    ->count();
                // Sent: Documents sent by handler to recipient handlers
                $stats['sent'] = Document::whereHas('logs', function($q) use ($user) {
                        $q->where('user_id', $user->id)
                          ->where('action', 'send');
                    })
                    ->count();
                // Received: Documents received by handler
                $stats['received'] = Document::whereHas('logs', function($q) use ($user) {
                        $q->where('user_id', $user->id)
                          ->where('action', 'receive');
                    })
                    ->count();
                // Rejected: Documents rejected by handler
                $stats['rejected'] = Document::whereHas('logs', function($q) use ($user) {
                        $q->where('user_id', $user->id)
                          ->where('action', 'reject');
                    })
                    ->count();
            }
        } elseif ($role === 'auditor') {
            // Auditors are redirected directly to audit logs
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['redirect' => '/api/audit-logs']);
            }
            return redirect()->route('audit.logs');
        }

        // Return JSON for API requests
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name ?? $user->email,
                    'email' => $user->email,
                    'role' => $user->role,
                    'department_id' => $user->department_id,
                ],
                'stats' => $stats,
                'recent_documents' => $recentDocuments->map(function($doc) use ($user) {
                    // Ensure API response also caps sender-visible post-receipt statuses
                    $status = $doc->current_status;
                    $statusLower = is_string($status) ? strtolower($status) : $status;
                    $isWithRecipientOwner = $doc->current_owner_id && $doc->current_owner_id !== $user->id;
                    if ($isWithRecipientOwner && in_array($statusLower, ['received','archived','completed','deleted'], true)) {
                        $status = 'received';
                    }
                    return [
                        'id' => $doc->id,
                        'title' => $doc->title,
                        'document_type' => $doc->document_type,
                        'current_status' => $status,
                        'owner' => $doc->owner->name ?? $doc->owner->email ?? null,
                        'created_at' => $doc->created_at->format('Y-m-d H:i:s'),
                    ];
                }),
                'pending_routes' => $pendingRoutes->map(function($route) {
                    return [
                        'id' => $route->id,
                        'document_id' => $route->document_id,
                        'document_title' => $route->document->title ?? null,
                        'from_department_id' => $route->from_department_id,
                        'to_department_id' => $route->to_department_id,
                        'status' => $route->new_status ?? 'Sent',
                        'created_at' => $route->created_at->format('Y-m-d H:i:s'),
                    ];
                }),
            ]);
        }

        return match ($role) {
            'admin' => view('admin.dashboard', compact('user','departments','recentDocuments','pendingRoutes','stats')),
            'owner' => view('owner.dashboard', compact('user','departments','recentDocuments','stats')),
            'handler' => view('handler.dashboard', compact('user','departments','recentDocuments','pendingRoutes','stats')),
            default => view('handler.dashboard', compact('user','departments','recentDocuments','pendingRoutes','stats')),
        };
    }
}
