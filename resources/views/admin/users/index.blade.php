<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Document Tracking System</title>
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
                        <button type="button" class="nav-btn active" data-page="users" onclick="navigateTo('users')">
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
                        <div class="user-avatar"><img src="{{ Auth::user()->profile_picture_url ?? 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . (Auth::id() ?? 0) }}" alt="{{ Auth::user()->name ?? Auth::user()->email ?? 'User' }} avatar"></div>
                        <div class="user-details">
                            <p class="user-name">{{ Auth::user()->name ?? Auth::user()->email ?? 'User' }}</p>
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
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="page-title mb-0">MANAGE USERS</h1>
                    <button class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#createUserModal">
                        <i class="bi bi-person-plus-fill me-2"></i>Create User
                    </button>
                </div>

                @if (session('status'))
                <div class="alert alert-success mb-4">{{ session('status') }}</div>
                @endif
                
                @if (session('error'))
                <div class="alert alert-danger mb-4">{{ session('error') }}</div>
                @endif

                <div class="row g-4">
                    <!-- Users List -->
                    <div class="col-12">
                        <div class="users-list-card">
                            <div class="list-header">
                                <div>
                                    <h2 class="list-title">
                                        <i class="bi bi-people-fill me-2"></i>Users List
                                    </h2>
                                    <p class="list-subtitle">Total: {{ $users->total() }} users</p>
                                </div>
                                <div class="search-box">
                                    <i class="bi bi-search"></i>
                                    <input type="text" id="userSearch" placeholder="Search users..." class="form-control" style="font-size: 0.8125rem; padding: 0.5rem 0.75rem 0.5rem 2.5rem;" />
                                </div>
                            </div>
                            <!-- Filter Section -->
                            <div class="px-3 pt-2 pb-2 border-bottom bg-light">
                                <div class="row g-2 align-items-end">
                                    <div class="col-md-3">
                                        <label class="form-label small mb-1" style="font-size: 0.75rem;">
                                            <i class="bi bi-shield-check"></i> Filter by Role
                                        </label>
                                        <select id="filterRole" class="form-select form-select-sm" style="font-size: 0.8125rem; padding: 0.375rem 0.5rem;">
                                            <option value="">All Roles</option>
                                            <option value="admin" {{ request('role') == 'admin' ? 'selected' : '' }}>Administrator</option>
                                            <option value="owner" {{ request('role') == 'owner' ? 'selected' : '' }}>Owner</option>
                                            <option value="handler" {{ request('role') == 'handler' ? 'selected' : '' }}>Handler</option>
                                            <option value="auditor" {{ request('role') == 'auditor' ? 'selected' : '' }}>Auditor</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small mb-1" style="font-size: 0.75rem;">
                                            <i class="bi bi-building"></i> Filter by Department
                                        </label>
                                        <select id="filterDepartment" class="form-select form-select-sm" style="font-size: 0.8125rem; padding: 0.375rem 0.5rem;">
                                            <option value="">All Departments</option>
                                            @foreach($departments as $dept)
                                                <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <button type="button" id="clearFiltersBtn" class="btn btn-outline-secondary btn-sm w-100" onclick="clearFilters()" style="display: none; font-size: 0.8125rem; padding: 0.375rem 0.5rem;">
                                            <i class="bi bi-x-circle me-1"></i>Clear Filters
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <!-- Filter Tabs -->
                            <div class="px-3 pt-3 border-bottom">
                                <ul class="nav nav-tabs" id="userFilterTabs" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link active" id="all-tab" data-bs-toggle="tab" data-bs-target="#all-users" type="button" role="tab">
                                            <i class="bi bi-people me-1"></i>All Users
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="deactivated-tab" data-bs-toggle="tab" data-bs-target="#deactivated-users" type="button" role="tab">
                                            <i class="bi bi-pause-circle me-1"></i>Deactivated
                                        </button>
                                    </li>
                                </ul>
                            </div>
                            <div class="table-responsive">
                                <table class="users-table">
                                    <thead>
                                        <tr>
                                            <th><i class="bi bi-person me-1"></i>Name</th>
                                            <th><i class="bi bi-envelope me-1"></i>Email</th>
                                            <th><i class="bi bi-shield-check me-1"></i>Role</th>
                                            <th><i class="bi bi-building me-1"></i>Department</th>
                                            <th><i class="bi bi-info-circle me-1"></i>Status</th>
                                            <th class="text-center">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="usersTableBody">
                                        @forelse($users as $u)
                                        <tr data-status="{{ ($u->is_active ?? true) && !$u->deleted_at ? 'active' : 'deactivated' }}" data-role="{{ $u->role }}" data-department="{{ $u->department_id ?? '' }}" class="{{ ($u->is_active ?? true) && !$u->deleted_at ? '' : 'table-secondary opacity-75' }}">
                                            <td>
                                                <div class="user-name-cell">
                                                    <div class="user-avatar-sm">
                                                        <img src="{{ $u->profile_picture_url ?? 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . $u->id }}" alt="Avatar">
                                                    </div>
                                                    <span class="name-text">{{ !empty($u->name) ? $u->name : $u->email }}</span>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="email-text">{{ $u->email }}</span>
                                            </td>
                                            <td>
                                                <span class="role-badge role-{{ $u->role }}">{{ ucfirst($u->role) }}</span>
                                            </td>
                                            <td>
                                                <span class="department-text">{{ $u->department->name ?? '—' }}</span>
                                            </td>
                                            <td>
                                                @if(($u->is_active ?? true) && !$u->deleted_at)
                                                    <span class="status-badge status-active">
                                                        <span class="status-indicator"></span>
                                                        <span class="status-text">Active</span>
                                                    </span>
                                                @else
                                                    <span class="status-badge status-inactive">
                                                        <span class="status-indicator"></span>
                                                        <span class="status-text">Inactive</span>
                                                    </span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    @if(($u->is_active ?? true) && !$u->deleted_at)
                                                        {{-- Active users: show edit button, and manage button only for non-admin users --}}
                                                        <button class="btn btn-sm btn-outline-primary btn-edit-user" 
                                                                title="Edit User" 
                                                                data-user-id="{{ $u->id }}" 
                                                                data-department-id="{{ $u->department_id ?? '' }}" 
                                                                data-user-name="{{ htmlspecialchars($u->name ?? '', ENT_QUOTES, 'UTF-8') }}" 
                                                                data-user-email="{{ htmlspecialchars($u->email ?? '', ENT_QUOTES, 'UTF-8') }}">
                                                            <i class="bi bi-pencil"></i>
                                                        </button>
                                                        @if($u->role !== 'admin')
                                                            <button class="btn btn-sm btn-outline-danger btn-manage-user" 
                                                                    title="Manage Account" 
                                                                    data-user-id="{{ $u->id }}" 
                                                                    data-user-role="{{ $u->role }}" 
                                                                    data-user-name="{{ htmlspecialchars($u->name ?? '', ENT_QUOTES, 'UTF-8') }}" 
                                                                    data-user-email="{{ htmlspecialchars($u->email ?? '', ENT_QUOTES, 'UTF-8') }}" 
                                                                    data-is-active="{{ ($u->is_active ?? true) ? '1' : '0' }}" 
                                                                    data-is-deleted="{{ $u->deleted_at ? '1' : '0' }}">
                                                                <i class="bi bi-gear"></i>
                                                            </button>
                                                        @endif
                                                    @else
                                                        {{-- Deactivated users: show reactivate and delete buttons directly --}}
                                                        <button class="btn btn-sm btn-success btn-reactivate-user" 
                                                                title="Reactivate Account" 
                                                                data-user-id="{{ $u->id }}" 
                                                                data-user-name="{{ htmlspecialchars($u->name ?? '', ENT_QUOTES, 'UTF-8') }}" 
                                                                data-user-email="{{ htmlspecialchars($u->email ?? '', ENT_QUOTES, 'UTF-8') }}">
                                                            <i class="bi bi-play-circle me-1"></i>Reactivate
                                                        </button>
                                                        <button class="btn btn-sm btn-danger btn-delete-user-direct" 
                                                                title="Permanently Delete Account" 
                                                                data-user-id="{{ $u->id }}" 
                                                                data-user-name="{{ htmlspecialchars($u->name ?? '', ENT_QUOTES, 'UTF-8') }}" 
                                                                data-user-email="{{ htmlspecialchars($u->email ?? '', ENT_QUOTES, 'UTF-8') }}">
                                                            <i class="bi bi-trash me-1"></i>Delete
                                                        </button>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="6" class="text-center py-5">
                                                <i class="bi bi-inbox" style="font-size: 3rem; color: #d1d5db; display: block; margin-bottom: 1rem;"></i>
                                                <p class="text-muted">No users found</p>
                                            </td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            <div class="list-footer">
                                <div class="pagination-wrapper">
                                    {{ $users->withQueryString()->links() }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Create User Modal -->
    <div class="modal fade" id="createUserModal" tabindex="-1" aria-labelledby="createUserModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createUserModalLabel">
                        <i class="bi bi-person-plus-fill me-2"></i>Create New User
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="{{ route('admin.users.store') }}" id="createUserForm">
                    @csrf
                    <div class="modal-body">
                        <div class="form-group mb-3">
                            <label class="form-label">
                                <i class="bi bi-person"></i> Full Name
                            </label>
                            <input name="name" type="text" placeholder="Enter full name" class="form-control form-control-lg" required />
                        </div>
                        <div class="form-group mb-3">
                            <label class="form-label">
                                <i class="bi bi-envelope"></i> Email Address
                            </label>
                            <input name="email" type="email" placeholder="user@example.com" class="form-control form-control-lg" required />
                        </div>
                        <div class="form-group mb-3">
                            <label class="form-label">
                                <i class="bi bi-lock"></i> Password
                            </label>
                            <input type="password" name="password" placeholder="Enter password (min. 6 characters)" class="form-control form-control-lg" required />
                        </div>
                        <div class="form-group mb-3">
                            <label class="form-label">
                                <i class="bi bi-shield-check"></i> Role
                            </label>
                            <select name="role" class="form-select form-select-lg" required>
                                <option value="">Select a role...</option>
                                <option value="admin">Administrator</option>
                                <option value="owner">Owner</option>
                                <option value="handler">Handler</option>
                                <option value="auditor">Auditor</option>
                            </select>
                        </div>
                        <div class="form-group mb-3">
                            <label class="form-label">
                                <i class="bi bi-building"></i> Department
                            </label>
                            <select name="department_id" class="form-select form-select-lg">
                                <option value="">No department</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-plus-circle-fill me-2"></i>Create User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit User Department Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editUserModalLabel">
                        <i class="bi bi-pencil-fill me-2"></i>Update User
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editUserForm">
                    @csrf
                    <input type="hidden" id="editUserId" name="user_id">
                    <div class="modal-body">
                        <div class="form-group mb-3">
                            <label class="form-label">
                                <i class="bi bi-envelope"></i> Email Address
                            </label>
                            <input type="email" name="email" id="editUserEmail" class="form-control form-control-lg" required>
                            <small class="form-text text-muted">This email will receive notifications when documents are updated.</small>
                        </div>
                        <div class="form-group mb-3">
                            <label class="form-label">
                                <i class="bi bi-building"></i> Department
                            </label>
                            <select name="department_id" id="editUserDepartment" class="form-select form-select-lg">
                                <option value="">No department</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle-fill me-2"></i>Update User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Manage User Account Modal -->
    <div class="modal fade" id="manageUserModal" tabindex="-1" aria-labelledby="manageUserModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title" id="manageUserModalLabel">
                        <i class="bi bi-gear-fill me-2"></i>Manage User Account
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p><strong>User:</strong> <span id="manageUserName"></span></p>
                    <p><strong>Email:</strong> <span id="manageUserEmail"></span></p>
                    <hr>
                    <div id="manageUserStatus" class="mb-3"></div>
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-warning" id="deactivateUserBtn" style="display: none;">
                            <i class="bi bi-pause-circle-fill me-2"></i>Deactivate Account
                        </button>
                        <button type="button" class="btn btn-success" id="activateUserBtn" style="display: none;">
                            <i class="bi bi-play-circle-fill me-2"></i>Activate Account
                        </button>
                        <button type="button" class="btn btn-danger" id="deleteUserBtn">
                            <i class="bi bi-trash-fill me-2"></i>Permanently Delete Account
                        </button>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Deactivate User Confirmation Modal -->
    <div class="modal fade" id="deactivateUserConfirmModal" tabindex="-1" aria-labelledby="deactivateUserConfirmModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title" id="deactivateUserConfirmModalLabel">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>Confirm Deactivation
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to deactivate this account?</p>
                    <p class="mb-0"><strong>User:</strong> <span id="deactivateConfirmUserName"></span></p>
                    <p class="text-muted small mt-2">The user will not be able to log in after deactivation.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-warning" id="confirmDeactivateBtn">
                        <i class="bi bi-pause-circle-fill me-2"></i>Deactivate Account
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete User Confirmation Modal -->
    <div class="modal fade" id="deleteUserConfirmModal" tabindex="-1" aria-labelledby="deleteUserConfirmModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteUserConfirmModalLabel">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>⚠️ WARNING: Permanent Deletion
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger">
                        <strong>This will PERMANENTLY DELETE the user account:</strong>
                        <p class="mb-0 mt-2"><strong id="deleteConfirmUserName"></strong></p>
                    </div>
                    <p class="text-danger"><strong>This action cannot be undone!</strong></p>
                    <p class="text-muted small">All data associated with this account will be permanently removed from the system.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                        <i class="bi bi-trash-fill me-2"></i>Permanently Delete Account
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('jss/dashboard.js') }}"></script>
    <style>
        /* User Management Specific Styles */
        .user-form-card {
            background: white;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            padding: 2rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .form-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f3f4f6;
        }

        .form-header i {
            font-size: 1.5rem;
            color: var(--navy-900);
        }

        .form-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #111827;
            margin: 0;
        }

        .form-group {
            position: relative;
        }

        .form-label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
        }

        .form-label i {
            color: var(--navy-800);
        }

        .form-control-lg, .form-select-lg {
            padding: 0.75rem 1rem;
            font-size: 0.9375rem;
            border-radius: 0.5rem;
            border: 1px solid #d1d5db;
            transition: all 0.2s ease;
        }

        .form-control-lg:focus, .form-select-lg:focus {
            border-color: var(--navy-800);
            box-shadow: 0 0 0 3px rgba(30, 58, 138, 0.1);
        }

        .btn-lg {
            padding: 0.875rem 1.5rem;
            font-weight: 600;
            border-radius: 0.5rem;
            transition: all 0.2s ease;
        }

        .btn-primary {
            background-color: var(--navy-900);
            border-color: var(--navy-900);
        }

        .btn-primary:hover {
            background-color: var(--navy-800);
            border-color: var(--navy-800);
            transform: translateY(-1px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .users-list-card {
            background: white;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .list-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem 2rem;
            border-bottom: 1px solid #e5e7eb;
            background: linear-gradient(to right, #f9fafb, #ffffff);
        }

        .list-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #111827;
            margin: 0;
            display: flex;
            align-items: center;
        }

        .list-subtitle {
            font-size: 0.875rem;
            color: #6b7280;
            margin: 0.25rem 0 0 0;
        }

        .search-box {
            position: relative;
            width: 300px;
        }

        .search-box i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
        }

        .search-box input {
            padding-left: 2.5rem;
            border-radius: 0.5rem;
            border: 1px solid #d1d5db;
        }

        /* Table responsive container - prevents layout shift when scrollbar appears */
        .table-responsive {
            /* Modern browsers: reserve space for scrollbar to prevent layout shift */
            scrollbar-gutter: stable;
            overflow-x: auto;
            overflow-y: hidden;
            -webkit-overflow-scrolling: touch;
            /* Smooth scrolling */
            scroll-behavior: smooth;
            /* Prevent content jump */
            padding-bottom: 0;
            margin-bottom: 0;
            /* Firefox scrollbar styling */
            scrollbar-width: thin;
            scrollbar-color: #888 #f1f1f1;
        }

        /* Custom scrollbar styling for better UX (Webkit browsers) */
        .table-responsive::-webkit-scrollbar {
            height: 12px;
        }

        .table-responsive::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 6px;
            margin: 0 4px;
        }

        .table-responsive::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 6px;
            border: 2px solid #f1f1f1;
        }

        .table-responsive::-webkit-scrollbar-thumb:hover {
            background: #555;
        }

        /* Fallback for browsers without scrollbar-gutter support */
        @supports not (scrollbar-gutter: stable) {
            .table-responsive {
                /* Add padding to compensate for scrollbar width */
                padding-right: 17px;
            }
            
            .table-responsive::-webkit-scrollbar {
                width: 17px;
            }
        }

        .users-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px; /* Ensure table has minimum width */
        }

        .users-table thead {
            background: linear-gradient(to right, var(--navy-900), var(--navy-800));
        }

        .users-table thead th {
            padding: 0.75rem 1rem;
            color: white;
            font-weight: 600;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .users-table tbody tr {
            border-bottom: 1px solid #f3f4f6;
            transition: all 0.15s ease;
        }

        .users-table tbody tr:hover {
            background-color: #f9fafb;
            transform: scale(1.001);
        }

        .users-table td {
            padding: 0.875rem 1rem;
            font-size: 0.8125rem;
        }

        .user-name-cell {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .user-avatar-sm {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            overflow: hidden;
            border: 2px solid #e5e7eb;
            flex-shrink: 0;
        }

        .user-avatar-sm img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .name-text {
            font-weight: 500;
            color: #111827;
        }

        .email-text {
            color: #6b7280;
        }

        .role-badge {
            display: inline-block;
            padding: 0.25rem 0.625rem;
            border-radius: 0.375rem;
            font-size: 0.6875rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.025em;
        }

        .role-admin {
            background-color: #fee2e2;
            color: #991b1b;
        }

        .role-owner {
            background-color: #dbeafe;
            color: #1e40af;
        }

        .role-handler {
            background-color: #d1fae5;
            color: #065f46;
        }

        .role-auditor {
            background-color: #fef3c7;
            color: #92400e;
        }

        .department-text {
            color: #374151;
            font-weight: 500;
        }

        /* Status Badge Styles */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.25rem 0.625rem;
            border-radius: 0.5rem;
            font-size: 0.75rem;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .status-indicator {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .status-active {
            background-color: #f0fdf4;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .status-active .status-indicator {
            background-color: #22c55e;
            box-shadow: 0 0 0 2px rgba(34, 197, 94, 0.2);
        }

        .status-inactive {
            background-color: #fffbeb;
            color: #854d0e;
            border: 1px solid #fde68a;
        }

        .status-inactive .status-indicator {
            background-color: #f59e0b;
            box-shadow: 0 0 0 2px rgba(245, 158, 11, 0.2);
        }

        .status-text {
            line-height: 1;
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
            justify-content: center;
            align-items: center;
            flex-wrap: wrap;
        }

        .action-buttons .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 0.375rem;
            transition: all 0.2s ease;
            font-size: 0.8125rem;
            font-weight: 500;
            white-space: nowrap;
            padding: 0.375rem 0.75rem;
            min-height: 32px;
        }

        /* Icon-only buttons (edit, manage) */
        .action-buttons .btn:not(.btn-reactivate-user):not(.btn-delete-user-direct) {
            width: 32px;
            height: 32px;
            padding: 0;
        }

        /* Text buttons (reactivate, delete) */
        .action-buttons .btn.btn-reactivate-user,
        .action-buttons .btn.btn-delete-user-direct {
            min-width: auto;
            padding: 0.375rem 0.75rem;
        }

        .action-buttons .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .action-buttons .btn:active {
            transform: translateY(0);
        }

        .list-footer {
            padding: 1.5rem 2rem;
            border-top: 1px solid #e5e7eb;
            background-color: #f9fafb;
        }

        @media (max-width: 992px) {
            .list-header {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }

            .search-box {
                width: 100%;
            }
        }

        .alert {
            border-radius: 0.5rem;
            border: none;
            padding: 1rem 1.5rem;
        }

        .alert-success {
            background-color: #d1fae5;
            color: #065f46;
        }

        /* User Status Display in Modal */
        .user-status-display {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 1.25rem;
            border-radius: 0.625rem;
            border: 1px solid;
            transition: all 0.2s ease;
        }

        .status-indicator-large {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .status-active-display {
            background-color: #f0fdf4;
            border-color: #bbf7d0;
            color: #166534;
        }

        .status-active-display .status-indicator-large {
            background-color: #22c55e;
            box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.15);
        }

        .status-inactive-display {
            background-color: #fffbeb;
            border-color: #fde68a;
            color: #854d0e;
        }

        .status-inactive-display .status-indicator-large {
            background-color: #f59e0b;
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.15);
        }

        .status-label {
            font-size: 0.75rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            opacity: 0.7;
            margin-bottom: 0.25rem;
        }

        .status-value {
            font-size: 0.9375rem;
            font-weight: 600;
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

        /* Modal Styles */
        #createUserModal .modal-content {
            border-radius: 12px;
            border: none;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }

        #createUserModal .modal-header {
            border-bottom: 2px solid #f3f4f6;
            padding: 1.5rem;
        }

        #createUserModal .modal-title {
            font-weight: 700;
            color: #111827;
            font-size: 1.25rem;
        }

        #createUserModal .modal-body {
            padding: 1.5rem;
        }

        #createUserModal .modal-footer {
            border-top: 1px solid #e5e7eb;
            padding: 1rem 1.5rem;
        }

        #createUserModal .form-label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
        }

        #createUserModal .form-label i {
            color: var(--navy-800);
        }

        #createUserModal .form-control-lg,
        #createUserModal .form-select-lg {
            padding: 0.75rem 1rem;
            font-size: 0.9375rem;
            border-radius: 0.5rem;
            border: 1px solid #d1d5db;
            transition: all 0.2s ease;
        }

        #createUserModal .form-control-lg:focus,
        #createUserModal .form-select-lg:focus {
            border-color: var(--navy-800);
            box-shadow: 0 0 0 3px rgba(30, 58, 138, 0.1);
        }

        /* Edit User Modal Styles */
        #editUserModal .modal-content {
            border-radius: 12px;
            border: none;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }

        #editUserModal .modal-header {
            border-bottom: 2px solid #f3f4f6;
            padding: 1.5rem;
        }

        #editUserModal .modal-title {
            font-weight: 700;
            color: #111827;
            font-size: 1.25rem;
        }

        #editUserModal .modal-body {
            padding: 1.5rem;
        }

        #editUserModal .modal-footer {
            border-top: 1px solid #e5e7eb;
            padding: 1rem 1.5rem;
        }

        /* Manage User Modal Styles */
        #manageUserModal .modal-content {
            border-radius: 12px;
            border: none;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }

        #manageUserModal .modal-header {
            border-bottom: 2px solid rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
        }

        #manageUserModal .modal-body {
            padding: 1.5rem;
        }

        #manageUserModal .modal-footer {
            border-top: 1px solid #e5e7eb;
            padding: 1rem 1.5rem;
        }
    </style>
    <script>
        // Check if any filters are active and show/hide clear button
        function updateClearFilterButton() {
            const searchTerm = (document.getElementById('userSearch')?.value || '').trim();
            const filterRole = document.getElementById('filterRole')?.value || '';
            const filterDepartment = document.getElementById('filterDepartment')?.value || '';
            const activeTab = document.querySelector('#userFilterTabs button[data-bs-toggle="tab"].active');
            const currentFilter = activeTab?.getAttribute('data-bs-target') || '#all-users';
            const isDefaultTab = currentFilter === '#all-users';
            
            // Check if any filter is active
            const hasActiveFilters = searchTerm !== '' || filterRole !== '' || filterDepartment !== '' || !isDefaultTab;
            
            const clearBtn = document.getElementById('clearFiltersBtn');
            if (clearBtn) {
                clearBtn.style.display = hasActiveFilters ? 'block' : 'none';
            }
        }

        // Tab filter functionality
        const filterTabs = document.querySelectorAll('#userFilterTabs button[data-bs-toggle="tab"]');
        filterTabs.forEach(tab => {
            tab.addEventListener('shown.bs.tab', function(e) {
                applyFilters();
                updateClearFilterButton();
            });
        });

        // Filter and search functionality
        function applyFilters() {
            const searchTerm = (document.getElementById('userSearch')?.value || '').toLowerCase();
            const filterRole = document.getElementById('filterRole')?.value || '';
            const filterDepartment = document.getElementById('filterDepartment')?.value || '';
            const activeTab = document.querySelector('#userFilterTabs button[data-bs-toggle="tab"].active');
            const currentFilter = activeTab?.getAttribute('data-bs-target') || '#all-users';
            const rows = document.querySelectorAll('#usersTableBody tr');
            
            // Update clear filter button visibility
            updateClearFilterButton();
            
            // Use requestAnimationFrame to batch DOM updates and prevent flash
            requestAnimationFrame(() => {
                rows.forEach(row => {
                    const name = row.querySelector('.name-text')?.textContent.toLowerCase() || '';
                    const email = row.querySelector('.email-text')?.textContent.toLowerCase() || '';
                    const role = row.querySelector('.role-badge')?.textContent.toLowerCase() || '';
                    const department = row.querySelector('.department-text')?.textContent.toLowerCase() || '';
                    const status = row.getAttribute('data-status') || 'active';
                    const rowRole = row.getAttribute('data-role') || '';
                    const rowDepartment = row.getAttribute('data-department') || '';
                    
                    // Search matching
                    const matchesSearch = !searchTerm || name.includes(searchTerm) || email.includes(searchTerm) || 
                        role.includes(searchTerm) || department.includes(searchTerm);
                    
                    // Role filter matching
                    const matchesRole = !filterRole || rowRole === filterRole;
                    
                    // Department filter matching
                    const matchesDepartment = !filterDepartment || rowDepartment === filterDepartment;
                    
                    // Status filter matching
                    let matchesStatus = true;
                    if (currentFilter === '#all-users') {
                        // All Users tab: show only active users
                        matchesStatus = status === 'active';
                    } else if (currentFilter === '#deactivated-users') {
                        // Deactivated tab: show only deactivated users
                        matchesStatus = status === 'deactivated';
                    }
                    
                    // Apply all filters
                    const shouldShow = matchesSearch && matchesRole && matchesDepartment && matchesStatus;
                    
                    row.style.display = shouldShow ? '' : 'none';
                });
            });
        }

        // Search functionality
        document.getElementById('userSearch')?.addEventListener('input', function(e) {
            applyFilters();
            updateClearFilterButton();
        });

        // Role filter
        document.getElementById('filterRole')?.addEventListener('change', function(e) {
            updateClearFilterButton();
            const role = e.target.value;
            const params = new URLSearchParams(window.location.search);
            if (role) {
                params.set('role', role);
            } else {
                params.delete('role');
            }
            window.location.href = '{{ route('admin.users.index') }}?' + params.toString();
        });

        // Department filter
        document.getElementById('filterDepartment')?.addEventListener('change', function(e) {
            updateClearFilterButton();
            const department = e.target.value;
            const params = new URLSearchParams(window.location.search);
            if (department) {
                params.set('department_id', department);
            } else {
                params.delete('department_id');
            }
            window.location.href = '{{ route('admin.users.index') }}?' + params.toString();
        });

        // Clear filters function
        function clearFilters() {
            window.location.href = '{{ route('admin.users.index') }}';
        }

        // Show content after initialization to prevent flash
        document.addEventListener('DOMContentLoaded', function() {
            // Apply filters on page load
            applyFilters();
            // Check initial filter state on page load
            updateClearFilterButton();
            
            // Fade in content after everything is initialized
            const contentBody = document.getElementById('contentBody');
            if (contentBody) {
                // Use requestAnimationFrame to ensure smooth transition
                requestAnimationFrame(() => {
                    contentBody.style.opacity = '1';
                });
            }
            
            // Attach event listeners for edit/manage buttons using data attributes
            document.querySelectorAll('.btn-edit-user').forEach(btn => {
                btn.addEventListener('click', function() {
                    const userId = parseInt(this.getAttribute('data-user-id'));
                    const departmentId = this.getAttribute('data-department-id') || null;
                    const userName = this.getAttribute('data-user-name') || '';
                    const userEmail = this.getAttribute('data-user-email') || '';
                    editUser(userId, departmentId, userName, userEmail);
                });
            });
            
            document.querySelectorAll('.btn-manage-user').forEach(btn => {
                btn.addEventListener('click', function() {
                    const userId = parseInt(this.getAttribute('data-user-id'));
                    const userRole = this.getAttribute('data-user-role') || '';
                    const userName = this.getAttribute('data-user-name') || '';
                    const userEmail = this.getAttribute('data-user-email') || '';
                    const isActive = this.getAttribute('data-is-active') === '1';
                    const isDeleted = this.getAttribute('data-is-deleted') === '1';
                    manageUser(userId, userName, userEmail, isActive, isDeleted, userRole);
                });
            });
            
            // Reactivate user button handlers (for deactivated users tab)
            document.querySelectorAll('.btn-reactivate-user').forEach(btn => {
                btn.addEventListener('click', async function() {
                    const userId = parseInt(this.getAttribute('data-user-id'));
                    const userName = this.getAttribute('data-user-name') || '';
                    const button = this;
                    
                    // Close any open modals before showing confirmation
                    const openModals = document.querySelectorAll('.modal.show');
                    openModals.forEach(modalEl => {
                        const modal = bootstrap.Modal.getInstance(modalEl);
                        if (modal) {
                            modal.hide();
                        }
                    });
                    
                    // Wait a bit for modal to close, then show confirmation
                    setTimeout(async () => {
                        const confirmed = await confirmModal(
                            'Reactivate Account',
                            `Are you sure you want to reactivate ${userName}?`,
                            {
                                confirmText: 'Reactivate',
                                confirmClass: 'btn-success',
                                icon: 'bi-play-circle'
                            }
                        );
                        
                        if (confirmed) {
                            performReactivate(userId, userName, button);
                        }
                    }, 300);
                });
            });
            
            // Helper function for reactivate action
            async function performReactivate(userId, userName, button) {
                button.disabled = true;
                button.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Reactivating...';
                
                try {
                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                    const response = await fetch(`/admin/users/${userId}/activate`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken || '',
                            'Accept': 'application/json'
                        }
                    });
                    
                    const data = await response.json();
                    
                    if (response.ok) {
                        if (typeof showToast === 'function') {
                            showToast('User reactivated successfully');
                        } else {
                            alert('User reactivated successfully');
                        }
                        setTimeout(() => window.location.reload(), 500);
                    } else {
                        if (typeof showToast === 'function') {
                            showToast(data.message || 'Failed to reactivate user', 'error');
                        } else {
                            alert(data.message || 'Failed to reactivate user');
                        }
                        button.disabled = false;
                        button.innerHTML = '<i class="bi bi-play-circle me-1"></i>Reactivate';
                    }
                } catch (error) {
                    console.error('Error:', error);
                    if (typeof showToast === 'function') {
                        showToast('An error occurred while reactivating the user', 'error');
                    } else {
                        alert('An error occurred while reactivating the user');
                    }
                    button.disabled = false;
                    button.innerHTML = '<i class="bi bi-play-circle me-1"></i>Reactivate';
                }
            }
            
            // Delete user button handlers (for deactivated users tab)
            document.querySelectorAll('.btn-delete-user-direct').forEach(btn => {
                btn.addEventListener('click', async function() {
                    const userId = parseInt(this.getAttribute('data-user-id'));
                    const userName = this.getAttribute('data-user-name') || '';
                    const button = this;
                    
                    // Close any open modals before showing confirmation
                    const openModals = document.querySelectorAll('.modal.show');
                    openModals.forEach(modalEl => {
                        const modal = bootstrap.Modal.getInstance(modalEl);
                        if (modal) {
                            modal.hide();
                        }
                    });
                    
                    // Wait a bit for modal to close, then show confirmation
                    setTimeout(async () => {
                        const confirmed = await confirmModal(
                            'Permanently Delete Account',
                            `Are you sure you want to permanently delete ${userName}?<br><strong class="text-danger">This action cannot be undone!</strong>`,
                            {
                                confirmText: 'Delete Permanently',
                                confirmClass: 'btn-danger',
                                icon: 'bi-exclamation-triangle-fill',
                                headerClass: 'bg-danger text-white'
                            }
                        );
                        
                        if (confirmed) {
                            performDelete(userId, userName, button);
                        }
                    }, 300);
                });
            });
            
            // Helper function for delete action
            async function performDelete(userId, userName, button) {
                const finalConfirmed = await confirmModal(
                    'Final Confirmation',
                    `Final confirmation: Permanently delete ${userName}?<br><strong class="text-danger">This will remove all data associated with this account.</strong>`,
                    {
                        confirmText: 'Yes, Delete Permanently',
                        confirmClass: 'btn-danger',
                        icon: 'bi-exclamation-triangle-fill',
                        headerClass: 'bg-danger text-white'
                    }
                );
                
                if (!finalConfirmed) {
                    return;
                }
                
                button.disabled = true;
                button.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Deleting...';
                
                try {
                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                    const response = await fetch(`/admin/users/${userId}`, {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken || '',
                            'Accept': 'application/json'
                        }
                    });
                    
                    const data = await response.json();
                    
                    if (response.ok) {
                        if (typeof showToast === 'function') {
                            showToast('User permanently deleted');
                        } else {
                            alert('User permanently deleted');
                        }
                        setTimeout(() => window.location.reload(), 500);
                    } else {
                        if (typeof showToast === 'function') {
                            showToast(data.message || 'Failed to delete user', 'error');
                        } else {
                            alert(data.message || 'Failed to delete user');
                        }
                        button.disabled = false;
                        button.innerHTML = '<i class="bi bi-trash me-1"></i>Delete';
                    }
                } catch (error) {
                    console.error('Error:', error);
                    if (typeof showToast === 'function') {
                        showToast('An error occurred while deleting the user', 'error');
                    } else {
                        alert('An error occurred while deleting the user');
                    }
                    button.disabled = false;
                    button.innerHTML = '<i class="bi bi-trash me-1"></i>Delete';
                }
            }
        });

        // Edit user department
        let editModalInstance = null;
        function editUser(userId, departmentId, userName, userEmail) {
            const modalEl = document.getElementById('editUserModal');
            if (!editModalInstance) {
                editModalInstance = new bootstrap.Modal(modalEl);
            }
            
            document.getElementById('editUserId').value = userId;
            document.getElementById('editUserEmail').value = userEmail || '';
            document.getElementById('editUserDepartment').value = departmentId || '';
            
            editModalInstance.show();
        }

        // Manage user account (deactivate/activate/delete)
        let manageModalInstance = null;
        let currentManageHandler = null;
        
        function manageUser(userId, userName, userEmail, isActive, isDeleted, userRole = '') {
            const modalEl = document.getElementById('manageUserModal');
            if (!manageModalInstance) {
                manageModalInstance = new bootstrap.Modal(modalEl);
            }
            
            document.getElementById('manageUserName').textContent = userName;
            document.getElementById('manageUserEmail').textContent = userEmail;
            
            const statusDiv = document.getElementById('manageUserStatus');
            
            // Get button references
            let deactivateBtnRef = document.getElementById('deactivateUserBtn');
            let activateBtnRef = document.getElementById('activateUserBtn');
            let deleteBtnRef = document.getElementById('deleteUserBtn');
            
            // Show appropriate buttons based on status
            if (isActive && !isDeleted) {
                statusDiv.innerHTML = '<div class="user-status-display status-active-display"><span class="status-indicator-large"></span><div><div class="status-label">Account Status</div><div class="status-value">Active</div></div></div>';
                // Hide deactivate button for admin accounts
                if (userRole === 'admin') {
                    deactivateBtnRef.style.display = 'none';
                } else {
                    deactivateBtnRef.style.display = 'block';
                }
                activateBtnRef.style.display = 'none';
            } else {
                statusDiv.innerHTML = '<div class="user-status-display status-inactive-display"><span class="status-indicator-large"></span><div><div class="status-label">Account Status</div><div class="status-value">Inactive</div></div></div>';
                deactivateBtnRef.style.display = 'none';
                activateBtnRef.style.display = 'block';
            }
            
            // Remove previous handlers by cloning buttons
            const deactivateParent = deactivateBtnRef.parentNode;
            const activateParent = activateBtnRef.parentNode;
            const deleteParent = deleteBtnRef.parentNode;
            
            const newDeactivateBtn = deactivateBtnRef.cloneNode(true);
            const newActivateBtn = activateBtnRef.cloneNode(true);
            const newDeleteBtn = deleteBtnRef.cloneNode(true);
            
            deactivateParent.replaceChild(newDeactivateBtn, deactivateBtnRef);
            activateParent.replaceChild(newActivateBtn, activateBtnRef);
            deleteParent.replaceChild(newDeleteBtn, deleteBtnRef);
            
            deactivateBtnRef = newDeactivateBtn;
            activateBtnRef = newActivateBtn;
            deleteBtnRef = newDeleteBtn;
            
            // Deactivate handler
            if (deactivateBtnRef) {
                deactivateBtnRef.addEventListener('click', function() {
                    // Close the manage user modal first
                    if (manageModalInstance) {
                        manageModalInstance.hide();
                    }
                    
                    // Wait for modal to close, then show confirmation modal
                    setTimeout(() => {
                        // Show confirmation modal
                        const confirmModalEl = document.getElementById('deactivateUserConfirmModal');
                        const confirmModal = new bootstrap.Modal(confirmModalEl);
                        
                        document.getElementById('deactivateConfirmUserName').textContent = userName;
                        
                        // Remove previous confirm handler
                        const oldConfirmBtn = document.getElementById('confirmDeactivateBtn');
                        const newConfirmBtn = oldConfirmBtn.cloneNode(true);
                        oldConfirmBtn.parentNode.replaceChild(newConfirmBtn, oldConfirmBtn);
                        
                        // Add new handler
                        newConfirmBtn.addEventListener('click', async function() {
                            newConfirmBtn.disabled = true;
                            newConfirmBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Deactivating...';
                            
                            try {
                                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                                const response = await fetch(`/admin/users/${userId}/deactivate`, {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': csrfToken || '',
                                        'Accept': 'application/json'
                                    }
                                });
                                
                                const data = await response.json();
                                
                                if (response.ok) {
                                    confirmModal.hide();
                                    if (typeof showToast === 'function') {
                                        showToast('User deactivated successfully');
                                    } else {
                                        alert('User deactivated successfully');
                                    }
                                    setTimeout(() => window.location.reload(), 500);
                                } else {
                                    const errorMessage = data.message || data.error || 'Failed to deactivate user';
                                    if (typeof showToast === 'function') {
                                        showToast(errorMessage, 'error');
                                    } else {
                                        alert(errorMessage);
                                    }
                                    newConfirmBtn.disabled = false;
                                    newConfirmBtn.innerHTML = '<i class="bi bi-pause-circle-fill me-2"></i>Deactivate Account';
                                }
                            } catch (error) {
                                console.error('Error:', error);
                                if (typeof showToast === 'function') {
                                    showToast('An error occurred while deactivating the user', 'error');
                                } else {
                                    alert('An error occurred while deactivating the user');
                                }
                                newConfirmBtn.disabled = false;
                                newConfirmBtn.innerHTML = '<i class="bi bi-pause-circle-fill me-2"></i>Deactivate Account';
                            }
                        });
                        
                        confirmModal.show();
                    }, 300);
                });
            }
            
            // Activate handler
            if (activateBtnRef) {
                activateBtnRef.addEventListener('click', async function() {
                    activateBtnRef.disabled = true;
                    activateBtnRef.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Activating...';
                    
                    try {
                        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                        const response = await fetch(`/admin/users/${userId}/activate`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken || '',
                                'Accept': 'application/json'
                            }
                        });
                        
                        const data = await response.json();
                        
                        if (response.ok) {
                            manageModalInstance.hide();
                            showToast('User activated successfully');
                            setTimeout(() => window.location.reload(), 500);
                        } else {
                            showToast(data.message || 'Failed to activate user', 'error');
                            activateBtnRef.disabled = false;
                            activateBtnRef.innerHTML = '<i class="bi bi-play-circle-fill me-2"></i>Activate Account';
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        showToast('An error occurred while activating the user', 'error');
                        activateBtnRef.disabled = false;
                        activateBtnRef.innerHTML = '<i class="bi bi-play-circle-fill me-2"></i>Activate Account';
                    }
                });
            }
            
            // Delete handler
            if (deleteBtnRef) {
                deleteBtnRef.addEventListener('click', function() {
                    // Show confirmation modal
                    const deleteConfirmModalEl = document.getElementById('deleteUserConfirmModal');
                    const deleteConfirmModal = new bootstrap.Modal(deleteConfirmModalEl);
                    
                    document.getElementById('deleteConfirmUserName').textContent = userName;
                    
                    // Remove previous confirm handler
                    const oldConfirmDeleteBtn = document.getElementById('confirmDeleteBtn');
                    const newConfirmDeleteBtn = oldConfirmDeleteBtn.cloneNode(true);
                    oldConfirmDeleteBtn.parentNode.replaceChild(newConfirmDeleteBtn, oldConfirmDeleteBtn);
                    
                    // Add new handler
                    newConfirmDeleteBtn.addEventListener('click', async function() {
                        newConfirmDeleteBtn.disabled = true;
                        newConfirmDeleteBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Deleting...';
                        
                        try {
                            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                            const response = await fetch(`/admin/users/${userId}`, {
                                method: 'DELETE',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': csrfToken || '',
                                    'Accept': 'application/json'
                                }
                            });
                            
                            const data = await response.json();
                            
                            if (response.ok) {
                                deleteConfirmModal.hide();
                                manageModalInstance.hide();
                                showToast('User permanently deleted');
                                setTimeout(() => window.location.reload(), 500);
                            } else {
                                showToast(data.message || 'Failed to delete user', 'error');
                                newConfirmDeleteBtn.disabled = false;
                                newConfirmDeleteBtn.innerHTML = '<i class="bi bi-trash-fill me-2"></i>Permanently Delete Account';
                            }
                        } catch (error) {
                            console.error('Error:', error);
                            showToast('An error occurred while deleting the user', 'error');
                            newConfirmDeleteBtn.disabled = false;
                            newConfirmDeleteBtn.innerHTML = '<i class="bi bi-trash-fill me-2"></i>Permanently Delete Account';
                        }
                    });
                    
                    deleteConfirmModal.show();
                });
            }
            
            manageModalInstance.show();
        }

        // Reset form when modal is closed
        const createUserModal = document.getElementById('createUserModal');
        if (createUserModal) {
            createUserModal.addEventListener('hidden.bs.modal', function () {
                const form = document.getElementById('createUserForm');
                if (form) {
                    form.reset();
                    // Reset submit button
                    const submitButton = form.querySelector('button[type="submit"]');
                    if (submitButton) {
                        submitButton.disabled = false;
                        submitButton.innerHTML = '<i class="bi bi-plus-circle-fill me-2"></i>Create User';
                    }
                }
            });
        }

        // Edit user form submission
        document.getElementById('editUserForm')?.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const form = this;
            const userId = document.getElementById('editUserId').value;
            const formData = new FormData(form);
            const submitButton = form.querySelector('button[type="submit"]');
            const originalButtonText = submitButton.innerHTML;
            
            submitButton.disabled = true;
            submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Updating...';
            
            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                const email = document.getElementById('editUserEmail').value.trim();
                const departmentId = document.getElementById('editUserDepartment').value || null;
                
                const updateData = {};
                if (email) {
                    updateData.email = email;
                }
                if (departmentId !== null) {
                    updateData.department_id = departmentId;
                }
                
                const response = await fetch(`/admin/users/${userId}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken || '',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(updateData)
                });

                if (response.ok) {
                    const modal = bootstrap.Modal.getInstance(document.getElementById('editUserModal'));
                    modal.hide();
                    window.location.reload();
                } else {
                    const data = await response.json();
                    alert(data.message || 'Failed to update user');
                    submitButton.disabled = false;
                    submitButton.innerHTML = originalButtonText;
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred while updating the user');
                submitButton.disabled = false;
                submitButton.innerHTML = originalButtonText;
            }
        });

        // Form reset on successful submission and close modal
        document.getElementById('createUserForm')?.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const form = this;
            const formData = new FormData(form);
            const submitButton = form.querySelector('button[type="submit"]');
            const originalButtonText = submitButton.innerHTML;
            
            // Disable submit button
            submitButton.disabled = true;
            submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Creating...';
            
            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken || ''
                    }
                });

                if (response.ok) {
                    // Close modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('createUserModal'));
                    modal.hide();
                    
                    // Reset form
                    form.reset();
                    
                    // Reload page to show new user
                    window.location.reload();
                } else {
                    const data = await response.json();
                    let errorMessage = 'Failed to create user.';
                    
                    if (data.errors) {
                        const errors = Object.values(data.errors).flat();
                        errorMessage = errors.join('\n');
                    } else if (data.message) {
                        errorMessage = data.message;
                    }
                    
                    alert(errorMessage);
                    submitButton.disabled = false;
                    submitButton.innerHTML = originalButtonText;
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred while creating the user.');
                submitButton.disabled = false;
                submitButton.innerHTML = originalButtonText;
            }
        });
    </script>

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
                            <input type="file" id="profilePictureInput" name="profile_picture" accept="image/*" class="d-none" onchange="previewProfilePicture(this)">
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

    // Preview profile picture
    function previewProfilePicture(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('profilePicturePreview').src = e.target.result;
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    // Open Edit Profile Modal
    async function openEditProfileModal() {
        const modalEl = document.getElementById('editProfileModal');
        const modal = new bootstrap.Modal(modalEl);
        
        // Load current profile data
        try {
            const response = await fetch('/profile');
            const data = await response.json();
            
            document.getElementById('profileName').value = data.user.name || '';
            
            // Set profile picture preview
            const previewImg = document.getElementById('profilePicturePreview');
            if (data.user.profile_picture) {
                previewImg.src = data.user.profile_picture;
            } else {
                previewImg.src = `https://api.dicebear.com/7.x/avataaars/svg?seed={{ Auth::id() ?? 0 }}`;
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
                const modal = bootstrap.Modal.getInstance(document.getElementById('editProfileModal'));
                if (modal) modal.hide();
                
                if (typeof showToast === 'function') {
                    showToast('Profile updated successfully');
                } else {
                    alert('Profile updated successfully');
                }
                
                // Update avatar images immediately
                if (data.user && data.user.profile_picture) {
                    const avatarImages = document.querySelectorAll('.user-avatar img');
                    avatarImages.forEach(img => {
                        img.src = data.user.profile_picture + '?t=' + new Date().getTime();
                    });
                }
                
                // Reset button state on success
                submitButton.disabled = false;
                submitButton.innerHTML = originalButtonText;
                
                // Reload after a short delay to ensure all updates are visible
                setTimeout(() => {
                    window.location.reload(true);
                }, 500);
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
