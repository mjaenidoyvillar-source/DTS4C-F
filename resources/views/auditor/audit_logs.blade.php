<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Logs - Document Tracking System</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
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
                @if(Auth::user()->role === 'admin')
                <div class="nav-section">
                    <p class="nav-section-title">GENERAL</p>
                    <div class="nav-menu">
                        <button type="button" class="nav-btn" data-page="dashboard" onclick="navigateTo('dashboard')">
                            <i class="bi bi-grid-fill" style="font-size: 24px;"></i><span>Dashboard</span>
                        </button>
                    </div>
                </div>
                @endif
                @if(Auth::user()->role === 'admin')
                <div class="nav-section">
                    <p class="nav-section-title">ACTIVITY TRACKING</p>
                    <div class="nav-menu">
                        <button type="button" class="nav-btn" data-page="activity-logs" onclick="navigateTo('activity-logs')">
                            <i class="bi bi-activity" style="font-size: 18px;"></i><span>Activity Logs</span>
                        </button>
                    </div>
                </div>
                @endif
                <div class="nav-section">
                    <p class="nav-section-title">AUDIT & TRACKING</p>
                    <div class="nav-menu">
                        <button type="button" class="nav-btn active" data-page="audit-logs" onclick="navigateTo('audit-logs')">
                            <i class="bi bi-list-ul" style="font-size: 18px;"></i><span>Audit Logs</span>
                        </button>
                    </div>
                </div>
                @if(Auth::user()->role === 'admin')
                <div class="nav-section">
                    <p class="nav-section-title">SYSTEM MONITORING</p>
                    <div class="nav-menu">
                        <button type="button" class="nav-btn" data-page="bug-reports" onclick="navigateTo('bug-reports')">
                            <i class="bi bi-bug" style="font-size: 18px;"></i><span>Bug Reports</span>
                        </button>
                    </div>
                </div>
                @endif
                @if(Auth::user()->role === 'admin')
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
                @endif
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
                @if(Auth::user()->role !== 'admin')
                <div class="header-title">
                    <i class="bi bi-shield-check"></i>
                    <span>Audit Logs - Document Tracking System</span>
                </div>
                @endif
            </div>
            <div class="content-body" id="contentBody" style="opacity: 0; transition: opacity 0.2s ease-in;">
                <div class="audit-header">
                    <div>
                        <h1 class="page-title">AUDIT LOGS</h1>
                        <p class="page-subtitle">Track all system activities and document actions</p>
                    </div>
                    <div style="display: flex; gap: 1rem; align-items: center;">
                        <div class="stats-badge">
                            <i class="bi bi-clipboard-data"></i>
                            <span>{{ $logs->total() }} Total Entries</span>
                        </div>
                        <a href="{{ route('audit.logs.export', request()->query()) }}" class="btn btn-success" style="display: flex; align-items: center; gap: 0.5rem; white-space: nowrap;">
                            <i class="bi bi-download"></i>
                            <span>Export CSV</span>
                        </a>
                    </div>
                </div>

                <div class="filter-card">
                    <h3 class="filter-title"><i class="bi bi-funnel"></i> Filter Logs</h3>
                    <form method="GET" class="filter-form" id="auditFilterForm">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-3">
                                <label class="form-label small mb-1" style="font-size: 0.75rem;">
                                    <i class="bi bi-building"></i> Filter by Department
                                </label>
                                <select name="department_id" id="filterDepartmentId" class="form-select form-select-sm" style="font-size: 0.8125rem; padding: 0.375rem 0.5rem;">
                                    <option value="">All Departments</option>
                                    @foreach($departments as $dept)
                                        <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>
                                            {{ $dept->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
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
                            <div class="col-md-2">
                                <label class="form-label small mb-1" style="font-size: 0.75rem;">
                                    <i class="bi bi-file-text"></i> Document ID
                                </label>
                                <input type="number" name="document_id" id="filterDocumentId" class="form-control form-control-sm" placeholder="e.g. 123" value="{{ request('document_id') }}" style="font-size: 0.8125rem; padding: 0.375rem 0.5rem;" />
                            </div>
                            <div class="col-md-2">
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
                            <div class="col-md-2">
                                <button type="button" id="clearAuditFiltersBtn" class="btn btn-outline-secondary btn-sm w-100" onclick="clearAuditFilters()" style="display: none; font-size: 0.8125rem; padding: 0.375rem 0.5rem;">
                                    <i class="bi bi-x-circle me-1"></i>Clear Filters
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="audit-table-card">
                    <div class="table-header">
                        <h3 class="table-title">
                            <i class="bi bi-list-check"></i> Activity Log
                        </h3>
                        <div class="table-info">
                            Showing {{ $logs->firstItem() ?? 0 }} to {{ $logs->lastItem() ?? 0 }} of {{ $logs->total() }} entries
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="audit-table">
                            <thead>
                                <tr>
                                    <th><i class="bi bi-clock"></i> Timestamp</th>
                                    <th><i class="bi bi-person-circle"></i> User</th>
                                    <th><i class="bi bi-file-earmark"></i> Document</th>
                                    <th><i class="bi bi-lightning"></i> Action</th>
                                    <th><i class="bi bi-card-text"></i> Description</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($logs as $log)
                                <tr>
                                    <td>
                                        <div class="timestamp-cell">
                                            <i class="bi bi-calendar3"></i>
                                            <div>
                                                <div class="date-text">{{ $log->created_at->format('M d, Y') }}</div>
                                                <div class="time-text">{{ $log->created_at->format('H:i:s') }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="user-cell">
                                            <div class="user-avatar-xs">
                                                <img src="{{ $log->user && $log->user->profile_picture_url ? $log->user->profile_picture_url : 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . ($log->user_id ?? 0) }}" alt="Avatar">
                                            </div>
                                            <div>
                                                <div class="user-name-text">{{ $log->user->name ?? $log->user->email ?? 'System' }}</div>
                                                @if($log->user && $log->user->role)
                                                    <div class="user-role-text">{{ ucfirst($log->user->role) }}</div>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        @if($log->document_id)
                                            <a href="#" class="document-link" onclick="viewDocument({{ $log->document_id }}); return false;">
                                                #{{ $log->document_id }}
                                            </a>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="action-badge action-{{ strtolower($log->action_type) }}">
                                            {{ $log->action_type }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="description-cell" title="{{ $log->description }}">
                                            {{ $log->description }}
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="empty-state">
                                        <i class="bi bi-inbox" style="font-size: 3rem; color: #d1d5db; display: block; margin-bottom: 1rem;"></i>
                                        <p class="text-muted">No audit logs found.</p>
                                        @if(request()->hasAny(['department_id', 'user_id', 'document_id', 'action_type']))
                                            <a href="{{ route('audit.logs') }}" class="btn btn-outline-primary mt-2">Clear Filters</a>
                                        @endif
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
    <script>
        // Auto-submit form when filters change
        function autoSubmitAuditFilters() {
            const form = document.getElementById('auditFilterForm');
            if (form) {
                form.submit();
            }
        }
        
        // Clear all filters
        function clearAuditFilters() {
            window.location.href = '{{ route('audit.logs') }}';
        }
        
        // Check if any filters are active and show/hide clear button
        function updateAuditClearFilterButton() {
            const departmentId = document.getElementById('filterDepartmentId')?.value || '';
            const userId = document.getElementById('filterUserId')?.value || '';
            const documentId = document.getElementById('filterDocumentId')?.value || '';
            const actionType = document.getElementById('filterActionType')?.value || '';
            
            // Check if any filter is active
            const hasActiveFilters = departmentId !== '' || userId !== '' || documentId !== '' || actionType !== '';
            
            const clearBtn = document.getElementById('clearAuditFiltersBtn');
            if (clearBtn) {
                clearBtn.style.display = hasActiveFilters ? 'block' : 'none';
            }
        }
        
        // Check on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateAuditClearFilterButton();
            
            // Auto-submit when filters change
            const departmentSelect = document.getElementById('filterDepartmentId');
            const userSelect = document.getElementById('filterUserId');
            const documentInput = document.getElementById('filterDocumentId');
            const actionSelect = document.getElementById('filterActionType');
            
            if (departmentSelect) {
                departmentSelect.addEventListener('change', function() {
                    updateAuditClearFilterButton();
                    autoSubmitAuditFilters();
                });
            }
            
            if (userSelect) {
                userSelect.addEventListener('change', function() {
                    updateAuditClearFilterButton();
                    autoSubmitAuditFilters();
                });
            }
            
            if (documentInput) {
                // Use debounce for input field to avoid too many submissions
                let debounceTimer;
                documentInput.addEventListener('input', function() {
                    updateAuditClearFilterButton();
                    clearTimeout(debounceTimer);
                    debounceTimer = setTimeout(() => {
                        autoSubmitAuditFilters();
                    }, 500); // Wait 500ms after user stops typing
                });
            }
            
            if (actionSelect) {
                actionSelect.addEventListener('change', function() {
                    updateAuditClearFilterButton();
                    autoSubmitAuditFilters();
                });
            }
            
            // Fade in content after everything is initialized to prevent flash
            const contentBody = document.getElementById('contentBody');
            const auditTableCard = document.getElementById('auditTableCard');
            if (contentBody) {
                requestAnimationFrame(() => {
                    contentBody.style.opacity = '1';
                });
            }
            if (auditTableCard) {
                requestAnimationFrame(() => {
                    auditTableCard.style.opacity = '1';
                });
            }
        });
    </script>
    <script src="{{ asset('jss/dashboard.js') }}"></script>
    <script src="{{ asset('js/profile.js') }}"></script>
    <style>
        /* Audit Logs Specific Styles */
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
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .filter-title {
            font-size: 1.125rem;
            font-weight: 700;
            color: #111827;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .filter-title i {
            color: var(--navy-900);
        }

        .filter-form .form-label {
            font-size: 0.875rem;
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

        .date-text {
            font-weight: 600;
            color: #111827;
        }

        .time-text {
            font-size: 0.8125rem;
            color: #6b7280;
        }

        .user-cell {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .user-avatar-xs {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            overflow: hidden;
            border: 2px solid #e5e7eb;
            flex-shrink: 0;
        }

        .user-avatar-xs img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .user-name-text {
            font-weight: 500;
            color: #111827;
        }

        .user-role-text {
            font-size: 0.75rem;
            color: #6b7280;
        }

        .document-link {
            color: var(--navy-800);
            font-weight: 600;
            text-decoration: none;
        }

        .document-link:hover {
            text-decoration: underline;
        }

        .action-badge {
            display: inline-block;
            padding: 0.375rem 0.75rem;
            border-radius: 0.375rem;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.025em;
        }

        .action-register { background-color: #dbeafe; color: #1e40af; }
        .action-send { background-color: #fef3c7; color: #92400e; }
        .action-receive { background-color: #d1fae5; color: #065f46; }
        .action-hold { background-color: #fee2e2; color: #991b1b; }
        .action-resume { background-color: #e0e7ff; color: #3730a3; }
        .action-forward { background-color: #f3e8ff; color: #6b21a8; }
        .action-complete { background-color: #d1fae5; color: #065f46; }
        .action-archive { background-color: #f3f4f6; color: #374151; }
        .action-unarchive { background-color: #e0e7ff; color: #3730a3; }
        .action-delete { background-color: #fee2e2; color: #991b1b; }
        .action-update { background-color: #fef3c7; color: #92400e; }

        .description-cell {
            max-width: 400px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            color: #374151;
        }

        .empty-state {
            padding: 4rem 2rem !important;
            text-align: center;
        }

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

        .header-title {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: white;
            font-weight: 600;
            font-size: 1rem;
        }

        .header-title i {
            font-size: 1.25rem;
        }
    }
    </style>

    <!-- Edit Profile Modal -->
    <div class="modal fade" id="editProfileModal" tabindex="-1" aria-labelledby="editProfileModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editProfileModalLabel">
                        <i class="bi bi-person-gear me-2"></i>Edit Profile
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editProfileForm" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3 text-center">
                            <label class="form-label">
                                <i class="bi bi-image"></i> Profile Picture
                            </label>
                            <div class="mb-2">
                                <img id="profilePicturePreview" src="https://api.dicebear.com/7.x/avataaars/svg?seed={{ Auth::id() ?? 0 }}" alt="Profile Picture" class="rounded-circle" style="width: 100px; height: 100px; object-fit: cover; border: 3px solid #dee2e6; cursor: pointer;" onclick="document.getElementById('profilePictureInput').click()">
                            </div>
                            <input type="file" id="profilePictureInput" name="profile_picture" accept="image/*" class="d-none" onchange="previewProfilePicture(this); if(this.files && this.files[0]) { const btn = document.getElementById('removePictureButtonContainer'); if(btn) { btn.style.setProperty('display', 'block', 'important'); btn.classList.remove('d-none'); } }">
                            <div id="removePictureButtonContainer" style="display: none !important;" class="mb-2">
                                <button type="button" class="btn btn-sm btn-danger" onclick="removeProfilePicture()">
                                    <i class="bi bi-trash"></i> Remove Picture
                                </button>
                            </div>
                            <small class="text-muted d-block">Click image to change. Max size: 2MB</small>
                        </div>
                        <hr>
                        <div class="mb-3">
                            <label class="form-label">
                                <i class="bi bi-person"></i> Full Name
                            </label>
                            <input type="text" name="name" id="profileName" class="form-control form-control-lg" />
                        </div>
                        <hr>
                        <h6 class="mb-3">Change Password (Optional)</h6>
                        <div class="mb-3">
                            <label class="form-label">
                                <i class="bi bi-lock"></i> Current Password
                            </label>
                            <div class="position-relative">
                                <input type="password" name="current_password" id="profileCurrentPassword" class="form-control form-control-lg" />
                                <button type="button" class="btn btn-link position-absolute end-0 top-50 translate-middle-y pe-3" style="border: none; background: none; z-index: 10;" onclick="togglePasswordVisibility('profileCurrentPassword', this)">
                                    <i class="bi bi-eye" id="eye-profileCurrentPassword"></i>
                                </button>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">
                                <i class="bi bi-key"></i> New Password
                            </label>
                            <div class="position-relative">
                                <input type="password" name="password" id="profilePassword" class="form-control form-control-lg" />
                                <button type="button" class="btn btn-link position-absolute end-0 top-50 translate-middle-y pe-3" style="border: none; background: none; z-index: 10;" onclick="togglePasswordVisibility('profilePassword', this)">
                                    <i class="bi bi-eye" id="eye-profilePassword"></i>
                                </button>
                            </div>
                            <small class="form-text text-muted">Leave blank if you don't want to change your password</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">
                                <i class="bi bi-key-fill"></i> Confirm New Password
                            </label>
                            <div class="position-relative">
                                <input type="password" name="password_confirmation" id="profilePasswordConfirmation" class="form-control form-control-lg" />
                                <button type="button" class="btn btn-link position-absolute end-0 top-50 translate-middle-y pe-3" style="border: none; background: none; z-index: 10;" onclick="togglePasswordVisibility('profilePasswordConfirmation', this)">
                                    <i class="bi bi-eye" id="eye-profilePasswordConfirmation"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle-fill me-2"></i>Update Profile
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function viewDocument(documentId) {
            // TODO: Implement document view modal
            alert('View document #' + documentId + ' functionality will be implemented soon.');
        }

        // Toggle password visibility
        function togglePasswordVisibility(inputId, button) {
            const input = document.getElementById(inputId);
            const icon = button.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        }

        // Open Edit Profile Modal
        async function openEditProfileModal() {
            const modalEl = document.getElementById('editProfileModal');
            const modal = new bootstrap.Modal(modalEl);
            
            try {
                const response = await fetch('/profile');
                const data = await response.json();
                
                document.getElementById('profileName').value = data.user.name || '';
                initProfilePicture(data.user, {{ Auth::id() ?? 0 }});
                // Force-check remove button visibility for auditor (same as admin)
                if (data?.user) {
                    const hasPic = !!(data.user.profile_picture && data.user.profile_picture !== 'null' && data.user.profile_picture !== '');
                    const removeBtnContainer = document.getElementById('removePictureButtonContainer');
                    if (removeBtnContainer) {
                        if (hasPic) {
                            removeBtnContainer.style.setProperty('display', 'block', 'important');
                            removeBtnContainer.classList.remove('d-none');
                        } else {
                            removeBtnContainer.style.display = 'none';
                        }
                    }
                }
            } catch (error) {
                console.error('Error loading profile:', error);
            }
            
            modal.show();
        }

        // Handle profile form submission
        document.getElementById('editProfileForm')?.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const form = this;
            const submitButton = form.querySelector('button[type="submit"]');
            const originalButtonText = submitButton.innerHTML;
            
            submitButton.disabled = true;
            submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Updating...';
            
            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                const formData = new FormData();
                
                formData.append('_method', 'PUT');
                
                const name = document.getElementById('profileName').value.trim();
                if (name) formData.append('name', name);
                
                const currentPassword = document.getElementById('profileCurrentPassword').value;
                const password = document.getElementById('profilePassword').value;
                const passwordConfirmation = document.getElementById('profilePasswordConfirmation').value;
                
                if (currentPassword) formData.append('current_password', currentPassword);
                if (password) formData.append('password', password);
                if (passwordConfirmation) formData.append('password_confirmation', passwordConfirmation);
                
                const profilePictureInput = document.getElementById('profilePictureInput');
                if (profilePictureInput.files[0]) {
                    formData.append('profile_picture', profilePictureInput.files[0]);
                }
                
                const response = await fetch('/profile', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken || '',
                        'Accept': 'application/json'
                    },
                    body: formData
                });

                // Check if response is JSON before parsing
                let data = {};
                const contentType = response.headers.get('content-type');
                if (contentType && contentType.includes('application/json')) {
                    try {
                        data = await response.json();
                    } catch (parseError) {
                        console.error('Error parsing JSON response:', parseError);
                        throw new Error('Invalid response from server');
                    }
                } else {
                    // If not JSON, get text response for debugging
                    const textResponse = await response.text();
                    console.error('Non-JSON response received:', textResponse.substring(0, 500));
                    throw new Error('Server returned an invalid response. Please try again.');
                }

                if (response.ok) {
                    if (typeof showToast === 'function') {
                        showToast('Profile updated successfully');
                    } else {
                        alert('Profile updated successfully');
                    }
                    
                    if (typeof updateProfilePictureAfterUpload === 'function') {
                        updateProfilePictureAfterUpload(data, {{ Auth::id() ?? 0 }});
                    }
                    
                    setTimeout(() => {
                        const modal = bootstrap.Modal.getInstance(document.getElementById('editProfileModal'));
                        if (modal) modal.hide();
                    }, 500);
                    
                    // Reset button state on success
                    submitButton.disabled = false;
                    submitButton.innerHTML = originalButtonText;
                } else {
                    let errorMessage = 'Failed to update profile.';
                    if (data.errors) {
                        const errors = Object.values(data.errors).flat();
                        errorMessage = errors.join('\n');
                    } else if (data.message) {
                        errorMessage = data.message;
                    }
                    
                    if (typeof showToast === 'function') {
                        showToast(errorMessage, 'error');
                    } else {
                        alert(errorMessage);
                    }
                    submitButton.disabled = false;
                    submitButton.innerHTML = originalButtonText;
                }
            } catch (error) {
                console.error('Error updating profile:', error);
                const errorMessage = error.message || 'An error occurred while updating the profile. Please check your connection and try again.';
                
                if (typeof showToast === 'function') {
                    showToast(errorMessage, 'error');
                } else {
                    alert(errorMessage);
                }
                submitButton.disabled = false;
                submitButton.innerHTML = originalButtonText;
            }
        });
    </script>
</body>
</html>
