<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Departments - Document Tracking System</title>
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
                        <button type="button" class="nav-btn" data-page="users" onclick="navigateTo('users')">
                            <i class="bi bi-people-fill" style="font-size: 20px;"></i><span>Manage Users</span>
                        </button>
                        <button type="button" class="nav-btn active" data-page="departments" onclick="navigateTo('departments')">
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
                <h1 class="page-title">MANAGE DEPARTMENTS</h1>

                @if (session('status'))
                <div class="alert alert-success mb-4">{{ session('status') }}</div>
                @endif

                @if (session('error'))
                <div class="alert alert-danger mb-4">{{ session('error') }}</div>
                @endif

                <div class="row g-4">
                    <!-- Create Department Form -->
                    <div class="col-lg-4">
                        <div class="user-form-card">
                            <div class="form-header">
                                <i class="bi bi-building-fill-add"></i>
                                <h2 class="form-title" id="formTitle">Create New Department</h2>
                            </div>
                            <form method="POST" action="{{ route('admin.departments.store') }}" id="departmentForm">
                                @csrf
                                <input type="hidden" id="departmentId" name="department_id" value="">
                                <input type="hidden" id="formMethod" name="_method" value="POST">
                                
                                <div class="form-group mb-3">
                                    <label class="form-label">
                                        <i class="bi bi-building"></i> Department Name
                                    </label>
                                    <input name="name" id="departmentName" type="text" placeholder="Enter department name" class="form-control form-control-lg" required />
                                </div>
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary btn-lg" id="submitBtn">
                                        <i class="bi bi-plus-circle-fill me-2"></i>Create Department
                                    </button>
                                    <button type="button" class="btn btn-secondary btn-lg" id="cancelBtn" style="display:none;" onclick="resetForm()">
                                        <i class="bi bi-x-circle me-2"></i>Cancel
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Departments List -->
                    <div class="col-lg-8">
                        <div class="users-list-card">
                            <div class="list-header">
                                <div>
                                    <h2 class="list-title">
                                        <i class="bi bi-building me-2"></i>Departments List
                                    </h2>
                                    <p class="list-subtitle">Total: {{ $departments->total() }} departments</p>
                                </div>
                                <div class="search-box">
                                    <i class="bi bi-search"></i>
                                    <input type="text" id="departmentSearch" placeholder="Search departments..." class="form-control" />
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="users-table">
                                    <thead>
                                        <tr>
                                            <th><i class="bi bi-building me-1"></i>Department Name</th>
                                            <th><i class="bi bi-people me-1"></i>Users Count</th>
                                            <th><i class="bi bi-calendar me-1"></i>Created</th>
                                            <th class="text-center">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($departments as $dept)
                                        <tr>
                                            <td>
                                                <div class="user-name-cell">
                                                    <div class="user-avatar-sm" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-color: #667eea;">
                                                        <i class="bi bi-building text-white" style="font-size: 18px; display: flex; align-items: center; justify-content: center; height: 100%;"></i>
                                                    </div>
                                                    <span class="name-text">{{ $dept->name }}</span>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="department-text">{{ $dept->users_count ?? 0 }} user(s)</span>
                                            </td>
                                            <td>
                                                <span class="email-text">{{ $dept->created_at->format('M d, Y') }}</span>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <button class="btn btn-sm btn-outline-primary btn-edit-dept" 
                                                            title="Edit Department" 
                                                            data-dept-id="{{ $dept->id }}" 
                                                            data-dept-name="{{ htmlspecialchars($dept->name ?? '', ENT_QUOTES, 'UTF-8') }}">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger btn-delete-dept" 
                                                            title="Delete Department" 
                                                            data-dept-id="{{ $dept->id }}" 
                                                            data-dept-name="{{ htmlspecialchars($dept->name ?? '', ENT_QUOTES, 'UTF-8') }}" 
                                                            data-users-count="{{ $dept->users_count ?? 0 }}">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="4" class="text-center py-5">
                                                <i class="bi bi-inbox" style="font-size: 3rem; color: #d1d5db; display: block; margin-bottom: 1rem;"></i>
                                                <p class="text-muted">No departments found</p>
                                            </td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            <div class="list-footer">
                                <div class="pagination-wrapper">
                                    {{ $departments->withQueryString()->links() }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Department Confirmation Modal -->
    <div class="modal fade" id="deleteDepartmentModal" tabindex="-1" aria-labelledby="deleteDepartmentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteDepartmentModalLabel">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>Delete Department
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="deleteDepartmentError" class="alert alert-warning" style="display: none;">
                        <i class="bi bi-exclamation-circle me-2"></i>
                        <span id="deleteDepartmentErrorMessage"></span>
                    </div>
                    <div id="deleteDepartmentConfirm">
                        <p>Are you sure you want to delete the department <strong id="deleteDepartmentName"></strong>?</p>
                        <p class="text-danger mb-0"><strong>This action cannot be undone.</strong></p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteDepartmentBtn">
                        <i class="bi bi-trash-fill me-2"></i>Delete Department
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('jss/dashboard.js') }}"></script>
    <script>
        // Fade in content after everything is initialized to prevent flash
        document.addEventListener('DOMContentLoaded', function() {
            const contentBody = document.getElementById('contentBody');
            if (contentBody) {
                requestAnimationFrame(() => {
                    contentBody.style.opacity = '1';
                });
            }
            
            // Attach event listeners for edit/delete buttons using data attributes
            document.querySelectorAll('.btn-edit-dept').forEach(btn => {
                btn.addEventListener('click', function() {
                    const deptId = parseInt(this.getAttribute('data-dept-id'));
                    const deptName = this.getAttribute('data-dept-name') || '';
                    editDepartment(deptId, deptName);
                });
            });
            
            document.querySelectorAll('.btn-delete-dept').forEach(btn => {
                btn.addEventListener('click', function() {
                    const deptId = parseInt(this.getAttribute('data-dept-id'));
                    const deptName = this.getAttribute('data-dept-name') || '';
                    const usersCount = parseInt(this.getAttribute('data-users-count') || '0');
                    deleteDepartment(deptId, deptName, usersCount);
                });
            });
        });
    </script>
    <style>
        /* Department Management Specific Styles */
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

        .users-table {
            width: 100%;
            border-collapse: collapse;
        }

        .users-table thead {
            background: linear-gradient(to right, var(--navy-900), var(--navy-800));
        }

        .users-table thead th {
            padding: 1rem 1.5rem;
            color: white;
            font-weight: 600;
            font-size: 0.875rem;
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
            padding: 1.25rem 1.5rem;
            font-size: 0.9375rem;
        }

        .user-name-cell {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .user-avatar-sm {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            overflow: hidden;
            border: 2px solid #e5e7eb;
            flex-shrink: 0;
        }

        .name-text {
            font-weight: 500;
            color: #111827;
        }

        .email-text {
            color: #6b7280;
        }

        .department-text {
            color: #374151;
            font-weight: 500;
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
            justify-content: center;
        }

        .action-buttons .btn {
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 0.375rem;
            transition: all 0.2s ease;
        }

        .action-buttons .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
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

        .alert-danger {
            background-color: #fee2e2;
            color: #991b1b;
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

        /* Delete Modal Styles */
        #deleteDepartmentModal .modal-header {
            border-bottom: 2px solid rgba(255, 255, 255, 0.2);
        }

        #deleteDepartmentModal .modal-body {
            padding: 1.5rem;
        }

        #deleteDepartmentModal .modal-footer {
            border-top: 1px solid #dee2e6;
        }

        #deleteDepartmentModal .btn-danger {
            background-color: #dc3545;
            border-color: #dc3545;
        }

        #deleteDepartmentModal .btn-danger:hover {
            background-color: #bb2d3b;
            border-color: #b02a37;
        }
    </style>
    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        // Search functionality
        document.getElementById('departmentSearch')?.addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('.users-table tbody tr');
            
            rows.forEach(row => {
                const name = row.querySelector('.name-text')?.textContent.toLowerCase() || '';
                
                if (name.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });

        // Edit department
        function editDepartment(departmentId, departmentName) {
            document.getElementById('departmentId').value = departmentId;
            document.getElementById('departmentName').value = departmentName;
            document.getElementById('formMethod').value = 'PUT';
            document.getElementById('formTitle').textContent = 'Edit Department';
            document.getElementById('submitBtn').innerHTML = '<i class="bi bi-check-circle-fill me-2"></i>Update Department';
            document.getElementById('cancelBtn').style.display = 'block';
            
            // Update form action
            const form = document.getElementById('departmentForm');
            form.action = `/admin/departments/${departmentId}`;
            
            // Scroll to form
            document.querySelector('.user-form-card').scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        // Reset form
        function resetForm() {
            document.getElementById('departmentId').value = '';
            document.getElementById('departmentName').value = '';
            document.getElementById('formMethod').value = 'POST';
            document.getElementById('formTitle').textContent = 'Create New Department';
            document.getElementById('submitBtn').innerHTML = '<i class="bi bi-plus-circle-fill me-2"></i>Create Department';
            document.getElementById('cancelBtn').style.display = 'none';
            
            // Reset form action
            const form = document.getElementById('departmentForm');
            form.action = '{{ route('admin.departments.store') }}';
        }

        // Delete department - show modal
        let deleteModalInstance = null;
        let currentDeleteHandler = null;

        function deleteDepartment(departmentId, departmentName, usersCount) {
            const modalEl = document.getElementById('deleteDepartmentModal');
            if (!deleteModalInstance) {
                deleteModalInstance = new bootstrap.Modal(modalEl);
            }
            
            const errorDiv = document.getElementById('deleteDepartmentError');
            const errorMessage = document.getElementById('deleteDepartmentErrorMessage');
            const confirmDiv = document.getElementById('deleteDepartmentConfirm');
            const deleteNameSpan = document.getElementById('deleteDepartmentName');
            const confirmBtn = document.getElementById('confirmDeleteDepartmentBtn');
            
            // Reset modal state
            errorDiv.style.display = 'none';
            confirmDiv.style.display = 'block';
            confirmBtn.disabled = false;
            confirmBtn.innerHTML = '<i class="bi bi-trash-fill me-2"></i>Delete Department';
            deleteNameSpan.textContent = departmentName;
            
            // Check if department has users
            if (usersCount > 0) {
                errorDiv.style.display = 'block';
                confirmDiv.style.display = 'none';
                errorMessage.textContent = `Cannot delete department "${departmentName}". It has ${usersCount} user(s) assigned. Please remove or reassign users first.`;
                deleteModalInstance.show();
                return;
            }
            
            // Remove previous event listener if exists
            if (currentDeleteHandler) {
                confirmBtn.removeEventListener('click', currentDeleteHandler);
            }
            
            // Create new event handler
            currentDeleteHandler = async function() {
                confirmBtn.disabled = true;
                confirmBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Deleting...';
                
                try {
                    const response = await fetch(`/admin/departments/${departmentId}`, {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        }
                    });

                    const data = await response.json();

                    if (response.ok) {
                        deleteModalInstance.hide();
                        window.location.reload();
                    } else {
                        errorDiv.style.display = 'block';
                        confirmDiv.style.display = 'none';
                        errorMessage.textContent = data.message || 'Failed to delete department';
                        confirmBtn.disabled = false;
                        confirmBtn.innerHTML = '<i class="bi bi-trash-fill me-2"></i>Delete Department';
                    }
                } catch (error) {
                    console.error('Error:', error);
                    errorDiv.style.display = 'block';
                    confirmDiv.style.display = 'none';
                    errorMessage.textContent = 'An error occurred while deleting the department';
                    confirmBtn.disabled = false;
                    confirmBtn.innerHTML = '<i class="bi bi-trash-fill me-2"></i>Delete Department';
                }
            };
            
            // Add event listener
            confirmBtn.addEventListener('click', currentDeleteHandler);
            
            // Show confirmation modal
            deleteModalInstance.show();
        }

        // Handle form submission for update
        document.getElementById('departmentForm')?.addEventListener('submit', async function(e) {
            const departmentId = document.getElementById('departmentId').value;
            const formMethod = document.getElementById('formMethod').value;

            if (formMethod === 'PUT' && departmentId) {
                e.preventDefault();
                
                const formData = {
                    name: document.getElementById('departmentName').value,
                };

                try {
                    const response = await fetch(`/admin/departments/${departmentId}`, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(formData)
                    });

                    const data = await response.json();

                    if (response.ok) {
                        window.location.reload();
                    } else {
                        alert(data.message || 'Failed to update department');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    alert('An error occurred while updating the department');
                }
            }
            // If POST, let the form submit normally
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

