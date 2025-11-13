<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Logs - Document Tracking System</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
    <style>
        /* Content body - match audit logs exactly - no padding override */

        .audit-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 2rem;
        }

        .page-title {
            margin-bottom: 0.5rem;
            text-align: left;
        }

        .page-subtitle {
            color: #6b7280;
            font-size: 0.9375rem;
            margin: 0;
        }

        .stats-badge {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.25rem;
            background: linear-gradient(135deg, var(--navy-900), var(--navy-800));
            color: white;
            border-radius: 0.5rem;
            font-weight: 600;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .stats-badge i {
            font-size: 1.25rem;
        }

        .filter-card {
            background: white;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .filter-title {
            font-size: 1.125rem;
            font-weight: 700;
            color: #111827;
            margin: 0 0 1rem 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .filter-title i {
            color: var(--navy-900);
        }

        .filter-form .form-label {
            font-size: 0.75rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .filter-form .form-label i {
            color: var(--navy-800);
        }

        .audit-table-card {
            background: white;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .table-responsive {
            margin: 0;
            padding: 0;
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem 2rem;
            border-bottom: 1px solid #e5e7eb;
            background: linear-gradient(to right, #f9fafb, #ffffff);
        }

        .table-title {
            font-size: 1.125rem;
            font-weight: 700;
            color: #111827;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .table-info {
            font-size: 0.875rem;
            color: #6b7280;
        }

        .audit-table {
            width: 100%;
            border-collapse: collapse;
        }

        .audit-table thead {
            background: linear-gradient(to right, var(--navy-900), var(--navy-800));
        }

        .audit-table thead th {
            padding: 0.75rem 1rem;
            color: white;
            font-weight: 600;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            text-align: left;
        }

        .audit-table thead th i {
            margin-right: 0.5rem;
        }

        .audit-table tbody tr {
            border-bottom: 1px solid #f3f4f6;
            transition: all 0.15s ease;
        }

        .audit-table tbody tr:hover {
            background-color: #f9fafb;
        }

        .audit-table td {
            padding: 0.875rem 1rem;
            font-size: 0.8125rem;
            color: #111827;
            vertical-align: middle;
        }

        .timestamp-cell {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .timestamp-cell i {
            color: #9ca3af;
            font-size: 1.125rem;
        }

        .action-badge {
            display: inline-block;
            padding: 0.25rem 0.625rem;
            border-radius: 0.375rem;
            font-size: 0.6875rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.025em;
        }

        .action-register { background-color: #dbeafe; color: #1e40af; }
        .action-update { background-color: #fef3c7; color: #92400e; }
        .action-delete { background-color: #fee2e2; color: #991b1b; }
        .action-archive { background-color: #f3f4f6; color: #4b5563; }
        .action-accept { background-color: #d1fae5; color: #065f46; }
        .action-decline { background-color: #fee2e2; color: #991b1b; }
        .action-send { background-color: #dbeafe; color: #1e40af; }
        .action-receive { background-color: #d1fae5; color: #065f46; }

        .table-footer {
            padding: 1.5rem 2rem;
            border-top: 1px solid #e5e7eb;
            background-color: #f9fafb;
        }

        /* Pagination Styles for Bootstrap 5 */
        .pagination-wrapper {
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .pagination-wrapper nav {
            display: flex;
            justify-content: center;
        }

        .pagination-wrapper .pagination {
            margin: 0;
            flex-wrap: wrap;
        }

        .pagination-wrapper .pagination .page-item .page-link {
            color: var(--navy-900);
            border: 1px solid #dee2e6;
            padding: 0.5rem 0.75rem;
            margin: 0 0.125rem;
            border-radius: 0.375rem;
            transition: all 0.2s ease;
        }

        .pagination-wrapper .pagination .page-item .page-link:hover {
            background-color: var(--navy-900);
            color: white;
            border-color: var(--navy-900);
        }

        .pagination-wrapper .pagination .page-item.active .page-link {
            background-color: var(--navy-900);
            border-color: var(--navy-900);
            color: white;
        }

        .pagination-wrapper .pagination .page-item.disabled .page-link {
            color: #6c757d;
            pointer-events: none;
            background-color: #fff;
            border-color: #dee2e6;
        }

        @media (max-width: 992px) {
            .audit-header {
                flex-direction: column;
                gap: 1rem;
            }

            .table-header {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-logo">
                <img src="{{ asset('images/logo.png') }}" alt="DTS Logo">
                <div class="logo-text">DTS</div>
            </div>
            <div class="sidebar-nav">
                <div class="nav-section">
                    <p class="nav-section-title">GENERAL</p>
                    <div class="nav-menu">
                        <button type="button" class="nav-btn" data-page="dashboard" onclick="navigateTo('dashboard')">
                            <i class="bi bi-grid-fill" style="font-size: 24px;"></i><span>Dashboard</span>
                        </button>
                    </div>
                </div>
                <div class="nav-section">
                    <p class="nav-section-title">ACTIVITY TRACKING</p>
                    <div class="nav-menu">
                        <button type="button" class="nav-btn active" data-page="activity-logs" onclick="navigateTo('activity-logs')">
                            <i class="bi bi-activity" style="font-size: 18px;"></i><span>Activity Logs</span>
                        </button>
                    </div>
                </div>
                <div class="nav-section">
                    <p class="nav-section-title">AUDIT & TRACKING</p>
                    <div class="nav-menu">
                        <button type="button" class="nav-btn" data-page="logs" onclick="navigateTo('logs')">
                            <i class="bi bi-list-ul" style="font-size: 18px;"></i><span>Audit Logs</span>
                        </button>
                    </div>
                </div>
                <div class="nav-section">
                    <p class="nav-section-title">USER MANAGEMENT</p>
                    <div class="nav-menu">
                        <button type="button" class="nav-btn" data-page="users" onclick="navigateTo('users')">
                            <i class="bi bi-people-fill" style="font-size: 20px;"></i><span>Manage Users</span>
                        </button>
                        <button type="button" class="nav-btn" data-page="departments" onclick="navigateTo('departments')">
                            <i class="bi bi-building" style="font-size: 20px;"></i><span>Manage Departments</span>
                        </button>
                    </div>
                </div>
            </div>
            <div class="sidebar-user">
                <div class="dropdown w-100">
                    <button class="user-info w-100 bg-transparent border-0 text-start d-flex align-items-center" id="userMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="user-avatar"><img src="{{ Auth::user()->profile_picture_url ?? 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . (Auth::id() ?? 0) }}" alt="{{ Auth::user()->name ?? 'User' }} avatar"></div>
                        <div class="user-details">
                            <p class="user-name">{{ Auth::user()->name ?? 'User' }}</p>
                            <p class="user-department">{{ ucfirst(Auth::user()->role ?? 'User') }}</p>
                        </div>
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="userMenuButton">
                        <li><a class="dropdown-item" href="#" onclick="event.preventDefault(); openEditProfileModal();"><i class="bi bi-pencil me-2"></i>Edit Profile</a></li>
                        <li><a class="dropdown-item" href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                    </ul>
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">@csrf</form>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="main-header">
            </div>
            <div class="content-body" id="contentBody" style="opacity: 0; transition: opacity 0.2s ease-in;">
                <div class="audit-header">
                    <div>
                        <h1 class="page-title">ACTIVITY LOGS</h1>
                        <p class="page-subtitle">Complete log of all system activities and user actions</p>
                    </div>
                    <div style="display: flex; gap: 1rem; align-items: center;">
                        <div class="stats-badge">
                            <i class="bi bi-clipboard-data"></i>
                            <span>{{ $logs->total() }} Total Activities</span>
                        </div>
                        <a href="{{ route('admin.activity-logs.export', request()->query()) }}" class="btn btn-success" style="display: flex; align-items: center; gap: 0.5rem; white-space: nowrap;">
                            <i class="bi bi-download"></i>
                            <span>Export CSV</span>
                        </a>
                    </div>
                </div>

                <div class="filter-card">
                    <h3 class="filter-title"><i class="bi bi-funnel"></i> Filter Activities</h3>
                    <form method="GET" class="filter-form" id="activityFilterForm">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-4">
                                <label class="form-label small mb-1" style="font-size: 0.75rem;">
                                    <i class="bi bi-person"></i> Filter by User
                                </label>
                                <select name="user_id" id="filterUserId" class="form-select form-select-sm" style="font-size: 0.8125rem; padding: 0.375rem 0.5rem;">
                                    <option value="">All Users</option>
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                            {{ $user->name ?? $user->email }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small mb-1" style="font-size: 0.75rem;">
                                    <i class="bi bi-activity"></i> Action Type
                                </label>
                                <select name="action_type" id="filterActionType" class="form-select form-select-sm" style="font-size: 0.8125rem; padding: 0.375rem 0.5rem;">
                                    <option value="">All Actions</option>
                                    @foreach($actionTypes as $action)
                                        <option value="{{ $action }}" {{ request('action_type') == $action ? 'selected' : '' }}>
                                            {{ $action }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <button type="button" id="clearActivityFiltersBtn" class="btn btn-outline-secondary btn-sm w-100" onclick="clearActivityFilters()" style="display: none; font-size: 0.8125rem; padding: 0.375rem 0.5rem;">
                                    <i class="bi bi-x-circle me-1"></i>Clear Filters
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="audit-table-card">
                    <div class="table-header">
                        <h3 class="table-title">
                            <i class="bi bi-list-check"></i> System Activities
                        </h3>
                        <div class="table-info">Showing {{ $logs->firstItem() ?? 0 }} to {{ $logs->lastItem() ?? 0 }} of {{ $logs->total() }} activities</div>
                    </div>
                    <div class="table-responsive">
                        <table class="audit-table">
                            <thead>
                                <tr>
                                    <th><i class="bi bi-calendar3"></i> Timestamp</th>
                                    <th><i class="bi bi-person"></i> User</th>
                                    <th><i class="bi bi-activity"></i> Action</th>
                                    <th><i class="bi bi-info-circle"></i> Description</th>
                                    <th><i class="bi bi-globe"></i> IP Address</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($logs as $log)
                                <tr>
                                    <td>
                                        <div class="timestamp-cell">
                                            <i class="bi bi-clock"></i>
                                            <span>{{ $log->created_at->format('Y-m-d H:i:s') }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <strong>{{ $log->user->name ?? $log->user->email ?? 'System' }}</strong>
                                        @if($log->user)
                                            <br><small class="text-muted">{{ ucfirst($log->user->role ?? 'User') }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="action-badge action-{{ strtolower($log->action_type) }}">
                                            {{ $log->action_type }}
                                        </span>
                                    </td>
                                    <td>{{ $log->description }}</td>
                                    <td>
                                        <small class="text-muted">{{ $log->ip_address ?? '—' }}</small>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center py-5">
                                        <i class="bi bi-inbox" style="font-size: 3rem; color: #d1d5db; display: block; margin-bottom: 1rem;"></i>
                                        <p class="text-muted">No activities found</p>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="table-footer">
                        <div class="pagination-wrapper">
                            {{ $logs->withQueryString()->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('jss/dashboard.js') }}"></script>
    <script>
        // Auto-submit form when filters change
        function autoSubmitActivityFilters() {
            const form = document.getElementById('activityFilterForm');
            if (!form) return;
            
            const selects = form.querySelectorAll('select');
            
            selects.forEach(select => {
                select.addEventListener('change', function() {
                    updateClearButtonVisibility();
                    form.submit();
                });
            });
        }
        
        function updateClearButtonVisibility() {
            const form = document.getElementById('activityFilterForm');
            if (!form) return;
            
            const userId = form.querySelector('[name="user_id"]')?.value || '';
            const actionType = form.querySelector('[name="action_type"]')?.value || '';
            
            const clearBtn = document.getElementById('clearActivityFiltersBtn');
            if (clearBtn) {
                if (userId || actionType) {
                    clearBtn.style.display = 'inline-block';
                } else {
                    clearBtn.style.display = 'none';
                }
            }
        }
        
        function clearActivityFilters() {
            window.location.href = '{{ route("admin.activity-logs") ?? "/admin/activity-logs" }}';
        }
        
        function viewDocument(documentId) {
            // Navigate to document view or open modal
            window.location.href = `/documents/${documentId}/view`;
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            // Fade in content
            const contentBody = document.getElementById('contentBody');
            if (contentBody) {
                requestAnimationFrame(() => {
                    contentBody.style.opacity = '1';
                });
            }
            
            // Initialize auto-submit filters
            autoSubmitActivityFilters();
            updateClearButtonVisibility();
        });
    </script>
</body>
</html>

