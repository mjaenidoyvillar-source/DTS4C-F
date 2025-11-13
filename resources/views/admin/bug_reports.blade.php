<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bug Reports - Document Tracking System</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
    <style>
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

        .severity-badge {
            display: inline-block;
            padding: 0.25rem 0.625rem;
            border-radius: 0.375rem;
            font-size: 0.6875rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.025em;
        }

        .severity-critical { background-color: #fee2e2; color: #991b1b; }
        .severity-error { background-color: #fef3c7; color: #92400e; }
        .severity-warning { background-color: #dbeafe; color: #1e40af; }

        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.625rem;
            border-radius: 0.375rem;
            font-size: 0.6875rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.025em;
        }

        .status-open { background-color: #fee2e2; color: #991b1b; }
        .status-resolved { background-color: #d1fae5; color: #065f46; }
        .status-ignored { background-color: #f3f4f6; color: #4b5563; }

        .table-footer {
            padding: 1.5rem 2rem;
            border-top: 1px solid #e5e7eb;
            background-color: #f9fafb;
        }

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

        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
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
                        <button type="button" class="nav-btn" data-page="activity-logs" onclick="navigateTo('activity-logs')">
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
                @if(session('status'))
                <div class="alert alert-success me-3 mb-0 py-2 px-3">{{ session('status') }}</div>
                @endif
            </div>
            <div class="content-body" id="contentBody" style="opacity: 0; transition: opacity 0.2s ease-in;">
                <div class="audit-header">
                    <div>
                        <h1 class="page-title">BUG REPORTS</h1>
                        <p class="page-subtitle">System errors and exceptions tracked automatically</p>
                    </div>
                    <div class="stats-badge">
                        <i class="bi bi-bug"></i>
                        <span>{{ $bugReports->total() }} Total Reports</span>
                    </div>
                </div>

                <div class="filter-card">
                    <h3 class="filter-title"><i class="bi bi-funnel"></i> Filter Bug Reports</h3>
                    <form method="GET" class="filter-form" id="bugReportFilterForm">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-3">
                                <label class="form-label small mb-1" style="font-size: 0.75rem;">
                                    <i class="bi bi-flag"></i> Status
                                </label>
                                <select name="status" id="filterStatus" class="form-select form-select-sm" style="font-size: 0.8125rem; padding: 0.375rem 0.5rem;">
                                    <option value="">All Statuses</option>
                                    @foreach($statuses as $status)
                                        <option value="{{ $status }}" {{ request('status') == $status ? 'selected' : '' }}>
                                            {{ ucfirst($status) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small mb-1" style="font-size: 0.75rem;">
                                    <i class="bi bi-exclamation-triangle"></i> Severity
                                </label>
                                <select name="severity" id="filterSeverity" class="form-select form-select-sm" style="font-size: 0.8125rem; padding: 0.375rem 0.5rem;">
                                    <option value="">All Severities</option>
                                    @foreach($severities as $severity)
                                        <option value="{{ $severity }}" {{ request('severity') == $severity ? 'selected' : '' }}>
                                            {{ ucfirst($severity) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small mb-1" style="font-size: 0.75rem;">
                                    <i class="bi bi-person"></i> User
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
                            <div class="col-md-3">
                                <button type="button" id="clearFiltersBtn" class="btn btn-outline-secondary btn-sm w-100" onclick="clearFilters()" style="display: none; font-size: 0.8125rem; padding: 0.375rem 0.5rem;">
                                    <i class="bi bi-x-circle me-1"></i>Clear Filters
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="audit-table-card">
                    <div class="table-header">
                        <h3 class="table-title">
                            <i class="bi bi-bug"></i> Error Reports
                        </h3>
                        <div class="table-info">Showing {{ $bugReports->firstItem() ?? 0 }} to {{ $bugReports->lastItem() ?? 0 }} of {{ $bugReports->total() }} reports</div>
                    </div>
                    <div class="table-responsive">
                        <table class="audit-table">
                            <thead>
                                <tr>
                                    <th><i class="bi bi-calendar3"></i> Date</th>
                                    <th><i class="bi bi-exclamation-triangle"></i> Error</th>
                                    <th><i class="bi bi-flag"></i> Severity</th>
                                    <th><i class="bi bi-file-earmark"></i> File</th>
                                    <th><i class="bi bi-person"></i> User</th>
                                    <th><i class="bi bi-info-circle"></i> Status</th>
                                    <th><i class="bi bi-arrow-repeat"></i> Count</th>
                                    <th><i class="bi bi-gear"></i> Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($bugReports as $report)
                                <tr>
                                    <td>
                                        <div class="timestamp-cell">
                                            <i class="bi bi-clock"></i>
                                            <span>{{ $report->created_at->format('Y-m-d H:i:s') }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <strong>{{ Str::limit($report->message, 50) }}</strong>
                                        @if($report->error_type)
                                            <br><small class="text-muted">{{ class_basename($report->error_type) }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="severity-badge severity-{{ $report->severity }}">
                                            {{ $report->severity }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($report->file)
                                            <small>{{ Str::limit(basename($report->file), 30) }}</small>
                                            @if($report->line)
                                                <br><small class="text-muted">Line {{ $report->line }}</small>
                                            @endif
                                        @else
                                            <small class="text-muted">—</small>
                                        @endif
                                    </td>
                                    <td>
                                        <strong>{{ $report->user ? ($report->user->name ?? $report->user->email ?? 'Unknown') : 'System' }}</strong>
                                    </td>
                                    <td>
                                        <span class="status-badge status-{{ $report->status }}">
                                            {{ $report->status }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">{{ $report->occurrence_count }}</span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button type="button" class="btn btn-sm btn-info" onclick="viewBugReport({{ $report->id }})" title="View Details">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            @if($report->status === 'open')
                                            <button type="button" class="btn btn-sm btn-success" onclick="updateBugStatus({{ $report->id }}, 'resolved')" title="Mark as Resolved">
                                                <i class="bi bi-check-circle"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-secondary" onclick="updateBugStatus({{ $report->id }}, 'ignored')" title="Ignore">
                                                <i class="bi bi-x-circle"></i>
                                            </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center py-5">
                                        <i class="bi bi-inbox" style="font-size: 3rem; color: #d1d5db; display: block; margin-bottom: 1rem;"></i>
                                        <p class="text-muted">No bug reports found</p>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="table-footer">
                        <div class="pagination-wrapper">
                            {{ $bugReports->withQueryString()->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bug Report Detail Modal -->
    <div class="modal fade" id="bugReportModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Bug Report Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="bugReportContent">
                    <div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('jss/dashboard.js') }}"></script>
    <script>
        // Auto-submit form when filters change
        function autoSubmitFilters() {
            const form = document.getElementById('bugReportFilterForm');
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
            const form = document.getElementById('bugReportFilterForm');
            if (!form) return;
            
            const status = form.querySelector('[name="status"]')?.value || '';
            const severity = form.querySelector('[name="severity"]')?.value || '';
            const userId = form.querySelector('[name="user_id"]')?.value || '';
            
            const clearBtn = document.getElementById('clearFiltersBtn');
            if (clearBtn) {
                if (status || severity || userId) {
                    clearBtn.style.display = 'inline-block';
                } else {
                    clearBtn.style.display = 'none';
                }
            }
        }
        
        function clearFilters() {
            window.location.href = '{{ route("admin.bug-reports.index") }}';
        }

        async function viewBugReport(id) {
            const modalEl = document.getElementById('bugReportModal');
            const modal = new bootstrap.Modal(modalEl);
            const contentDiv = document.getElementById('bugReportContent');
            contentDiv.innerHTML = '<div class="text-center"><div class="spinner-border"></div></div>';
            modal.show();
            
            try {
                const response = await fetch(`/admin/bug-reports/${id}`);
                const report = await response.json();
                
                const traceHtml = report.trace ? `<pre class="bg-light p-3 rounded" style="font-size: 0.75rem; max-height: 300px; overflow-y: auto;">${escapeHtml(report.trace)}</pre>` : '<p class="text-muted">No stack trace available</p>';
                const requestDataHtml = report.request_data ? `<pre class="bg-light p-3 rounded" style="font-size: 0.75rem; max-height: 200px; overflow-y: auto;">${escapeHtml(JSON.stringify(report.request_data, null, 2))}</pre>` : '<p class="text-muted">No request data</p>';
                
                contentDiv.innerHTML = `
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Error Type:</strong><br>
                            <span class="badge bg-danger">${escapeHtml(report.error_type || 'Unknown')}</span>
                        </div>
                        <div class="col-md-6">
                            <strong>Severity:</strong><br>
                            <span class="severity-badge severity-${report.severity}">${report.severity}</span>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Status:</strong><br>
                            <span class="status-badge status-${report.status}">${report.status}</span>
                        </div>
                        <div class="col-md-6">
                            <strong>Occurrence Count:</strong><br>
                            <span class="badge bg-secondary">${report.occurrence_count}</span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <strong>Message:</strong>
                        <div class="alert alert-danger mt-2">${escapeHtml(report.message)}</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>File:</strong><br>
                            <code>${escapeHtml(report.file || '—')}</code>
                            ${report.line ? `<br><small class="text-muted">Line ${report.line}</small>` : ''}
                        </div>
                        <div class="col-md-6">
                            <strong>URL:</strong><br>
                            <code>${escapeHtml(report.url || '—')}</code>
                            ${report.method ? `<br><small class="text-muted">Method: ${report.method}</small>` : ''}
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>User:</strong><br>
                            ${report.user ? `${escapeHtml(report.user.name || report.user.email)}` : 'System'}
                        </div>
                        <div class="col-md-6">
                            <strong>IP Address:</strong><br>
                            <code>${escapeHtml(report.ip_address || '—')}</code>
                        </div>
                    </div>
                    <div class="mb-3">
                        <strong>User Agent:</strong><br>
                        <small class="text-muted">${escapeHtml(report.user_agent || '—')}</small>
                    </div>
                    <div class="mb-3">
                        <strong>Stack Trace:</strong>
                        ${traceHtml}
                    </div>
                    <div class="mb-3">
                        <strong>Request Data:</strong>
                        ${requestDataHtml}
                    </div>
                    ${report.resolution_notes ? `
                    <div class="mb-3">
                        <strong>Resolution Notes:</strong>
                        <div class="alert alert-info mt-2">${escapeHtml(report.resolution_notes)}</div>
                    </div>
                    ` : ''}
                    ${report.resolved_by ? `
                    <div class="mb-3">
                        <strong>Resolved By:</strong> ${escapeHtml(report.resolved_by.name || 'Unknown')}<br>
                        <strong>Resolved At:</strong> ${report.resolved_at || '—'}
                    </div>
                    ` : ''}
                    <div class="mb-3">
                        <strong>Created At:</strong> ${report.created_at}<br>
                        <strong>Last Updated:</strong> ${report.updated_at}
                    </div>
                `;
            } catch (error) {
                console.error(error);
                contentDiv.innerHTML = '<div class="alert alert-danger">Failed to load bug report details.</div>';
            }
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        async function updateBugStatus(id, status) {
            const confirmed = await confirmModal(
                'Update Bug Report Status',
                `Are you sure you want to mark this bug report as <strong>${status}</strong>?`,
                {
                    confirmText: 'Update Status',
                    confirmClass: 'btn-primary',
                    icon: 'bi-check-circle'
                }
            );
            
            if (!confirmed) {
                return;
            }

            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                const response = await fetch(`/admin/bug-reports/${id}/status`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken || ''
                    },
                    body: JSON.stringify({ status })
                });

                if (response.ok) {
                    window.location.reload();
                } else {
                    const data = await response.json();
                    alert(data.message || 'Failed to update bug report status');
                }
            } catch (error) {
                console.error(error);
                alert('An error occurred while updating the bug report status');
            }
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
            autoSubmitFilters();
            updateClearButtonVisibility();
        });
    </script>
</body>
</html>

