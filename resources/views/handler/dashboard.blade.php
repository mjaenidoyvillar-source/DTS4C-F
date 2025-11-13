<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Handler Dashboard - Document Tracking System</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
    <style>
        /* Document Status Badge Styles */
        .doc-status-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.375rem 0.75rem;
            border-radius: 0.5rem;
            font-size: 0.8125rem;
            font-weight: 500;
            text-transform: capitalize;
            letter-spacing: 0.01em;
            transition: all 0.2s ease;
        }
        
        /* Pending Statuses */
        .doc-status-pending,
        .doc-status-pending-handler-review,
        .doc-status-pending-recipient-handler,
        .doc-status-pending-recipient-owner,
        .doc-status-registered {
            background-color: #fef3c7;
            color: #92400e;
            border: 1px solid #fde68a;
        }
        
        /* Handler Review Statuses */
        .doc-status-handler-review,
        .doc-status-received-by-handler {
            background-color: #dbeafe;
            color: #1e40af;
            border: 1px solid #93c5fd;
        }
        
        /* Received Statuses */
        .doc-status-received,
        .doc-status-received-by-handler {
            background-color: #d1fae5;
            color: #065f46;
            border: 1px solid #86efac;
        }
        
        /* Completed Status */
        .doc-status-completed,
        .doc-status-complete {
            background-color: #dcfce7;
            color: #166534;
            border: 1px solid #86efac;
        }
        
        /* Rejected Status */
        .doc-status-rejected {
            background-color: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }
        
        /* Archived Status */
        .doc-status-archived {
            background-color: #f3f4f6;
            color: #4b5563;
            border: 1px solid #d1d5db;
        }
        
        /* For Review Status */
        .doc-status-for-review,
        .doc-status-for-owner-review {
            background-color: #e0e7ff;
            color: #3730a3;
            border: 1px solid #a5b4fc;
        }
        
        /* Sent Status */
        .doc-status-sent {
            background-color: #dbeafe;
            color: #1e40af;
            border: 1px solid #93c5fd;
        }
        
        /* Default/Unknown Status */
        .doc-status-default,
        .doc-status-badge:not([class*="doc-status-"]) {
            background-color: #f3f4f6;
            color: #6b7280;
            border: 1px solid #d1d5db;
        }
    </style>
</head>
<body>
    <!-- Mobile Menu Toggle Button -->
    <button class="mobile-menu-toggle" aria-label="Toggle menu">
        <i class="bi bi-list"></i>
    </button>
    
    <!-- Sidebar Overlay for Mobile -->
    <div class="sidebar-overlay"></div>
    
    <div class="dashboard-container">
        <div class="sidebar">
            <div class="sidebar-logo">
                <img src="{{ asset('images/logo.png') }}" alt="DTS Logo">
                <div class="logo-text">DTS</div>
            </div>
            <div class="sidebar-nav">
                <div class="nav-section">
                    <p class="nav-section-title">GENERAL</p>
                    <div class="nav-menu">
                        <button class="nav-btn active" data-page="dashboard" onclick="navigateTo('dashboard')">
                            <i class="bi bi-grid-fill" style="font-size: 24px;"></i><span>Dashboard</span>
                        </button>
                    </div>
                </div>
                <div class="nav-section">
                    <p class="nav-section-title">DOCUMENTS</p>
                    <div class="nav-menu">
                        <button class="nav-btn" data-page="on-hold" onclick="navigateTo('on-hold')">
                            <i class="bi bi-pause-circle" style="font-size: 18px;"></i><span>On Hold</span>
                        </button>
                    </div>
                </div>
            </div>
            <div class="sidebar-user">
                <div class="dropdown w-100">
                    <button class="user-info w-100 bg-transparent border-0 text-start d-flex align-items-center" id="userMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="user-avatar"><img src="{{ Auth::user()->profile_picture_url ?? 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . (Auth::id() ?? 0) }}" alt="{{ $user->name ?? 'User' }} avatar"></div>
                        <div class="user-details">
                            <p class="user-name">{{ $user->name ?? 'User' }}</p>
                            <p class="user-department">Handler</p>
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

        <div class="main-content">
            <div class="main-header">
                <button class="new-doc-btn" data-bs-toggle="modal" data-bs-target="#scanQRModal" onclick="initQRScanner()">
                    <i class="bi bi-qr-code-scan" style="font-size: 18px;"></i> Scan QR Code
                </button>
            </div>
            <div class="content-body">
                <h1 class="page-title">DOCUMENT TRACKING SYSTEM</h1>
                <div class="stats-grid" id="stats">
                    <div class="stat-card stat-red" onclick="navigateTo('on-hold')">
                        <div class="stat-number">{{ $stats['onHold'] ?? 0 }}</div>
                        <div class="stat-label">ON HOLD</div>
                    </div>
                    <div class="stat-card stat-blue" onclick="navigateTo('dashboard')">
                        <div class="stat-number">{{ $stats['sent'] ?? 0 }}</div>
                        <div class="stat-label">SENT</div>
                    </div>
                    <div class="stat-card stat-green" onclick="navigateTo('dashboard')">
                        <div class="stat-number">{{ $stats['received'] ?? 0 }}</div>
                        <div class="stat-label">RECEIVED</div>
                    </div>
                    <div class="stat-card stat-orange" onclick="navigateTo('dashboard')">
                        <div class="stat-number">{{ $stats['rejected'] ?? 0 }}</div>
                        <div class="stat-label">REJECTED</div>
                    </div>
                </div>

                <div id="recent-docs">
                    <h2 class="recent-section-title">Recent Documents</h2>
                    <div class="documents-table-container">
                        <table class="documents-table">
                            <thead>
                                <tr>
                                    <th>Title</th><th>Type</th><th>Description</th><th>Purpose</th><th>Sender</th><th>Sender Department</th><th>Receiver</th><th>Receiving Department</th><th>Date</th><th>File</th><th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse(($recentDocuments ?? []) as $doc)
                                <tr>
                                    <td>{{ $doc->title }}</td>
                                    <td>{{ $doc->document_type }}</td>
                                    <td>{{ \Illuminate\Support\Str::limit($doc->description, 60) }}</td>
                                    <td>{{ $doc->purpose }}</td>
                                    <td>{{ $doc->owner->name ?? $doc->owner->email ?? '—' }}</td>
                                    <td>{{ $doc->owner->department->name ?? '—' }}</td>
                                    <td>{{ $doc->targetOwner->name ?? $doc->targetOwner->email ?? '—' }}</td>
                                    <td>{{ optional($doc->receiving_department_id ? \App\Models\Department::find($doc->receiving_department_id) : null)->name }}</td>
                                    <td>{{ $doc->created_at->format('Y-m-d H:i') }}</td>
                                    <td>@if($doc->file_data || $doc->id)<button class="btn btn-sm btn-outline-info" onclick="viewDocumentModal({{ $doc->id }})" title="View"><i class="bi bi-eye"></i></button>@else — @endif</td>
                                    <td>
                                        <span class="doc-status-badge doc-status-{{ strtolower(str_replace(['_', ' '], '-', $doc->current_status)) }}">
                                            {{ ucwords(str_replace(['_', '-'], ' ', $doc->current_status)) }}
                                        </span>
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="11" class="text-center">No documents yet</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Send Document Modal -->
    <div class="modal fade" id="sendDocumentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog"><div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Send Document</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
            <form method="POST" id="sendDocumentForm">@csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Receiving Department</label>
                        <input type="text" class="form-control" id="receivingDeptDisplay" readonly style="background-color: #e9ecef;" />
                    </div>
                    <div class="mb-3"><label class="form-label">To User (optional)</label><input type="number" name="to_user_id" class="form-control" placeholder="Handler User ID" /></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary"><i class="bi bi-send me-1"></i>Send</button></div>
            </form>
        </div></div>
    </div>

    <!-- Document Details Modal -->
    <div class="modal fade" id="documentDetailsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Document Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="documentDetailsContent">
                    <div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <a href="#" id="viewFileLink" class="btn btn-primary" target="_blank" style="display:none;">Open File</a>
                </div>
            </div>
        </div>
    </div>

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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('jss/dashboard.js') }}"></script>
    <script src="{{ asset('js/profile.js') }}"></script>
    <script>
    // Helper function to format status text (convert underscores/hyphens to spaces and capitalize)
    function formatStatusText(status) {
        if (!status || status === '—') return '—';
        return status.replace(/[_-]/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
    }
    
    // Helper function to get status CSS class
    function getStatusClass(status) {
        if (!status || status === '—') return 'doc-status-default';
        const normalized = status.toLowerCase().replace(/[_\s]/g, '-');
        
        // Map statuses to classes
        if (normalized.includes('pending') || normalized === 'registered') {
            if (normalized.includes('handler')) return 'doc-status-pending-handler-review';
            if (normalized.includes('recipient-handler')) return 'doc-status-pending-recipient-handler';
            if (normalized.includes('recipient-owner') || normalized.includes('owner')) return 'doc-status-pending-recipient-owner';
            return 'doc-status-pending';
        }
        if (normalized.includes('handler-review') || normalized.includes('received-by-handler')) {
            return 'doc-status-handler-review';
        }
        if (normalized === 'received') {
            return 'doc-status-received';
        }
        if (normalized === 'sent') {
            return 'doc-status-sent';
        }
        if (normalized === 'completed' || normalized === 'complete') {
            return 'doc-status-completed';
        }
        if (normalized === 'rejected') {
            return 'doc-status-rejected';
        }
        if (normalized === 'archived') {
            return 'doc-status-archived';
        }
        if (normalized.includes('for-review') || normalized.includes('for-owner-review')) {
            return 'doc-status-for-review';
        }
        
        // Default fallback
        return 'doc-status-default';
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

    const sendModal = document.getElementById('sendDocumentModal');
    if (sendModal) {
        sendModal.addEventListener('show.bs.modal', async function (event) {
            const button = event.relatedTarget;
            const docId = button?.getAttribute('data-document-id');
            const form = document.getElementById('sendDocumentForm');
            if (form && docId) {
                form.setAttribute('action', `/documents/${docId}/send`);
                // Fetch document details to get receiving department
                try {
                    const response = await fetch(`/documents/${docId}/details`);
                    const doc = await response.json();
                    document.getElementById('receivingDeptDisplay').value = doc.receiving_department || 'Not set';
                } catch (err) {
                    console.error('Failed to load document details', err);
                }
            }
        });
        const form = document.getElementById('sendDocumentForm');
        if (form) form.addEventListener('submit', async function(e){
            e.preventDefault();
            const formData = new FormData(form);
            const payload = Object.fromEntries(formData.entries());
            try { await postJson(form.getAttribute('action'), payload); showToast('Document sent'); location.reload(); }
            catch (err) { showToast('Failed to send', 'error'); }
        });
    }

    // Document View Modal for Handler
    async function viewDocumentModal(docId) {
        const modalEl = document.getElementById('documentViewModal');
        if (!modalEl) {
            const modalHtml = `<div class="modal fade" id="documentViewModal" tabindex="-1"><div class="modal-dialog modal-xl"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Document Details</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body" id="documentViewContent"></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button><a href="#" id="documentDownloadLink" class="btn btn-primary" download style="display:none;"><i class="bi bi-download me-1"></i>Download File</a></div></div></div></div>`;
            document.body.insertAdjacentHTML('beforeend', modalHtml);
        }
        const modal = new bootstrap.Modal(document.getElementById('documentViewModal'));
        const contentDiv = document.getElementById('documentViewContent');
        const downloadLink = document.getElementById('documentDownloadLink');
        contentDiv.innerHTML = '<div class="text-center"><div class="spinner-border"></div></div>';
        downloadLink.style.display = 'none';
        modal.show();
        try {
            const response = await fetch(`/documents/${docId}/details`);
            const doc = await response.json();
            
            // Generate QR code for document - always show container, generate QR code if libraries available
            const qrCodeHtml = `<div class="card border-primary mb-3"><div class="card-body text-center"><h6 class="card-title"><i class="bi bi-qr-code"></i> Document QR Code</h6><div id="qr-container-${docId}" style="min-height: 200px; display: flex; align-items: center; justify-content: center;"></div><p class="text-muted small mt-2">Scan this QR code to quickly access document details</p></div></div>`;
            
            const fileSize = doc.file_size ? (doc.file_size < 1024 ? doc.file_size + ' bytes' : doc.file_size < 1048576 ? (doc.file_size / 1024).toFixed(2) + ' KB' : (doc.file_size / 1048576).toFixed(2) + ' MB') : '—';
            const statusClass = getStatusClass(doc.current_status || '');
            const statusText = formatStatusText(doc.current_status || '—');
            contentDiv.innerHTML = `${qrCodeHtml}<div class="mb-4"><h4>${doc.title || 'Document'}</h4><p class="text-muted"><i class="bi bi-file-earmark"></i> Document Code: #${doc.id}</p></div><div class="row"><div class="col-md-6 mb-3"><strong>Document Type:</strong><br><span>${doc.document_type || '—'}</span></div><div class="col-md-6 mb-3"><strong>Status:</strong><br><span class="doc-status-badge ${statusClass}">${statusText}</span></div><div class="col-md-6 mb-3"><strong>Purpose:</strong><br><span>${doc.purpose || '—'}</span></div><div class="col-md-6 mb-3"><strong>Created Date:</strong><br><span>${doc.created_at || '—'}</span></div><div class="col-md-6 mb-3"><strong>Department:</strong><br><span>${doc.department || '—'}</span></div><div class="col-md-6 mb-3"><strong>Receiving Department:</strong><br><span>${doc.receiving_department || '—'}</span></div></div><div class="mb-3"><strong>Description:</strong><div class="mt-2 p-3 bg-light border-start border-primary border-4 rounded">${doc.description || 'No description provided.'}</div></div>${doc.has_file ? `<div class="p-4 bg-info bg-opacity-10 border border-info rounded mt-4"><h5 class="mb-3"><i class="bi bi-file-earmark-text"></i> File Information</h5><div class="row"><div class="col-md-6 mb-3"><strong>File Name:</strong><br><span>${doc.file_name || '—'}</span></div><div class="col-md-6 mb-3"><strong>File Type:</strong><br><span>${doc.file_mime || '—'}</span></div>${doc.file_size ? `<div class="col-md-6 mb-3"><strong>File Size:</strong><br><span>${fileSize}</span></div>` : ''}</div><div class="text-center mt-3"><p class="text-muted mb-0">Download the file to your device to view its contents</p></div></div>` : '<div class="alert alert-warning mt-4"><i class="bi bi-exclamation-triangle"></i> No file attached.</div>'}`;
            
            // Generate and append QR code canvas to container after HTML is set
            const qrContainer = document.getElementById('qr-container-' + docId);
            if (qrContainer) {
                // Wait for libraries to load with retry - give it more time to match library loading
                let retries = 0;
                const maxRetries = 60; // 12 seconds (60 * 200ms) to allow libraries to load
                
                function tryGenerateQR() {
                    // Check if QRCode library is available (primary requirement)
                    // Try both global QRCode and window.QRCode, and also check for toCanvas method
                    let QRCodeLib = null;
                    if (typeof QRCode !== 'undefined' && typeof QRCode.toCanvas === 'function') {
                        QRCodeLib = QRCode;
                    } else if (typeof window.QRCode !== 'undefined' && typeof window.QRCode.toCanvas === 'function') {
                        QRCodeLib = window.QRCode;
                    } else if (typeof QRCode !== 'undefined') {
                        QRCodeLib = QRCode;
                    } else if (typeof window.QRCode !== 'undefined') {
                        QRCodeLib = window.QRCode;
                    }
                    
                    if (QRCodeLib && typeof QRCodeLib.toCanvas === 'function') {
                        try {
                            const qrCanvas = document.createElement('canvas');
                            qrCanvas.id = 'qr-canvas-' + docId;
                            qrCanvas.style.maxWidth = '200px';
                            qrCanvas.style.margin = '0 auto';
                            
                            // Generate QR code directly using QRCode library
                            // Use consistent base URL from server to ensure same QR code across all roles
                            const baseUrl = '{{ config("app.url") }}'.replace(/\/$/, '');
                            const qrUrl = `${baseUrl}/documents/${docId}/details`;
                            QRCodeLib.toCanvas(qrCanvas, qrUrl, {
                                width: 200,
                                margin: 2,
                                color: {
                                    dark: '#000000',
                                    light: '#FFFFFF'
                                }
                            }).then(() => {
                                qrContainer.innerHTML = ''; // Clear any loading message
                                qrContainer.appendChild(qrCanvas);
                            }).catch((error) => {
                                console.error('Error generating QR code:', error);
                                qrContainer.innerHTML = '<p class="text-muted small">QR code generation failed: ' + (error.message || 'Unknown error') + '</p>';
                            });
                        } catch (error) {
                            console.error('Error creating QR canvas:', error);
                            qrContainer.innerHTML = '<p class="text-muted small">QR code generation failed: ' + (error.message || 'Unknown error') + '</p>';
                        }
                    } else if (retries < maxRetries) {
                        retries++;
                        setTimeout(tryGenerateQR, 200);
                    } else {
                        console.error('QRCode library not available after', maxRetries, 'retries');
                        console.log('QRCode type:', typeof QRCode);
                        console.log('window.QRCode type:', typeof window.QRCode);
                        console.log('Checking script tags...');
                        const scripts = document.querySelectorAll('script[src*="qrcode"]');
                        console.log('QRCode script tags found:', scripts.length);
                        scripts.forEach((s, i) => console.log('Script', i, ':', s.src));
                        qrContainer.innerHTML = '<p class="text-muted small">QR code library not loaded. Please refresh the page or check browser console for errors.</p>';
                    }
                }
                
                // Start after a small delay to allow DOM to settle
                setTimeout(tryGenerateQR, 100);
            }
            
            if (doc.has_file) {
                downloadLink.href = `/documents/${docId}/file`;
                downloadLink.style.display = 'inline-block';
            }
        } catch (error) {
            console.error(error);
            contentDiv.innerHTML = '<div class="alert alert-danger">Failed to load document details.</div>';
        }
    }

    // Document Details Modal
    const docDetailsModal = document.getElementById('documentDetailsModal');
    if (docDetailsModal) {
        docDetailsModal.addEventListener('show.bs.modal', async function (event) {
            const button = event.relatedTarget;
            const docId = button?.getAttribute('data-document-id');
            const contentDiv = document.getElementById('documentDetailsContent');
            const fileLink = document.getElementById('viewFileLink');
            
            if (!docId) return;
            
            contentDiv.innerHTML = '<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>';
            fileLink.style.display = 'none';
            
            try {
                const response = await fetch(`/documents/${docId}/details`);
                const doc = await response.json();
                
                // Generate QR code for document
                let qrCodeHtml = '';
                let qrCanvas = null;
                if (window.QRCodeHandler && typeof QRCode !== 'undefined') {
                    qrCanvas = document.createElement('canvas');
                    qrCanvas.id = 'qr-canvas-' + docId;
                    qrCanvas.style.maxWidth = '200px';
                    qrCanvas.style.margin = '0 auto';
                    await window.QRCodeHandler.generateQRCode(docId, qrCanvas, 200);
                    qrCodeHtml = `<div class="card border-primary mb-3"><div class="card-body text-center"><h6 class="card-title"><i class="bi bi-qr-code"></i> Document QR Code</h6><div id="qr-container-${docId}"></div><p class="text-muted small mt-2">Scan this QR code to quickly access document details</p></div></div>`;
                }
                
                contentDiv.innerHTML = `${qrCodeHtml}<div class="row">
                        <div class="col-md-6 mb-3">
                            <strong>Title:</strong><br>
                            <span>${doc.title || '—'}</span>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>Document Type:</strong><br>
                            <span>${doc.document_type || '—'}</span>
                        </div>
                        <div class="col-md-12 mb-3">
                            <strong>Description:</strong><br>
                            <span>${doc.description || '—'}</span>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>Purpose:</strong><br>
                            <span>${doc.purpose || '—'}</span>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>Status:</strong><br>
                            <span class="doc-status-badge ${getStatusClass(doc.current_status || '')}">${formatStatusText(doc.current_status || '—')}</span>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>Department:</strong><br>
                            <span>${doc.department || '—'}</span>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>Receiving Department:</strong><br>
                            <span>${doc.receiving_department || '—'}</span>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>File Name:</strong><br>
                            <span>${doc.file_name || '—'}</span>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>Created At:</strong><br>
                            <span>${doc.created_at || '—'}</span>
                        </div>
                    </div>`;
                
                // Append QR code canvas to container
                if (qrCodeHtml && qrCanvas) {
                    const qrContainer = document.getElementById('qr-container-' + docId);
                    if (qrContainer) {
                        qrContainer.appendChild(qrCanvas);
                    }
                }
                
                if (doc.has_file && doc.file_url) {
                    fileLink.href = doc.file_url;
                    fileLink.style.display = 'inline-block';
                }
            } catch (err) {
                contentDiv.innerHTML = '<div class="alert alert-danger">Failed to load document details.</div>';
            }
        });
    }

    // QR Code Scanning Modal
    function initQRScanner() {
        if (typeof Html5Qrcode === 'undefined') {
            console.error('QR Scanner library not loaded');
            return;
        }
    }

    async function handleQRScan(decodedText) {
        if (!window.QRCodeHandler) {
            console.error('QRCodeHandler not available');
            return;
        }

        const documentId = window.QRCodeHandler.extractDocumentId(decodedText);
        if (!documentId) {
            alert('Invalid QR code. Please scan a valid document QR code.');
            return;
        }

        await window.QRCodeHandler.stopScanning();
        const scanModal = bootstrap.Modal.getInstance(document.getElementById('scanQRModal'));
        if (scanModal) {
            scanModal.hide();
        }
        
        // Ensure viewDocumentModal is available
        if (typeof viewDocumentModal === 'function') {
            viewDocumentModal(documentId);
        } else {
            // Fallback: redirect to document view page
            window.location.href = `/documents/${documentId}/view`;
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const scanModal = document.getElementById('scanQRModal');
        if (scanModal) {
            scanModal.addEventListener('show.bs.modal', async function() {
                if (window.QRCodeHandler) {
                    await window.QRCodeHandler.startScanning(
                        'qr-reader',
                        handleQRScan,
                        (error) => {
                            // Show user-friendly error messages
                            if (error && !error.includes('No QR code found')) {
                                const errorDiv = document.getElementById('qr-reader-results');
                                if (errorDiv) {
                                    errorDiv.innerHTML = `<div class="alert alert-warning alert-dismissible fade show" role="alert">
                                        <i class="bi bi-exclamation-triangle me-2"></i>${error}
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    </div>`;
                                } else {
                                    alert(error);
                                }
                            }
                        }
                    );
                }
            });

            scanModal.addEventListener('hide.bs.modal', async function() {
                if (window.QRCodeHandler) {
                    await window.QRCodeHandler.stopScanning();
                }
            });
        }
    });
    </script>

    <!-- QR Code Scanning Modal -->
    <div class="modal fade" id="scanQRModal" tabindex="-1" aria-labelledby="scanQRModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="scanQRModalLabel">
                        <i class="bi bi-qr-code-scan me-2"></i>Scan Document QR Code
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <p class="text-muted mb-3">Position the QR code within the frame below</p>
                    <div id="qr-reader" style="width: 100%; min-height: 300px;"></div>
                    <div id="qr-reader-results" class="mt-3"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- QR Code Libraries - Load before functions that use them -->
    <!-- Load QRCode library - try local bundle first, then CDN fallbacks -->
    <script src="{{ asset('js/qrcode.min.js') }}" 
            onload="console.log('QRCode loaded from local bundle'); window.QRCodeLoaded = true;"
            onerror="
                console.warn('Local QRCode failed, trying CDN...');
                const script = document.createElement('script');
                script.src = 'https://cdn.jsdelivr.net/npm/qrcode@1.5.4/+esm';
                script.type = 'module';
                script.innerHTML = 'import(\"https://cdn.jsdelivr.net/npm/qrcode@1.5.4/+esm\").then(m => { window.QRCode = m.default || m; console.log(\"QRCode loaded from CDN ES module\"); });';
                document.head.appendChild(script);
                // Also try fallback CDN
                const fallbackScript = document.createElement('script');
                fallbackScript.src = 'https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js';
                fallbackScript.onload = function() { window.QRCode = QRCode; console.log('QRCode loaded from fallback CDN'); };
                document.head.appendChild(fallbackScript);
            "></script>
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js" onerror="console.error('Failed to load Html5Qrcode library')"></script>
    <script src="{{ asset('js/qrcode-handler.js') }}" onerror="console.error('Failed to load QRCodeHandler')"></script>
    
    <script>
    // Set app URL for QR code generation (ensures QR codes work in production)
    window.APP_URL = '{{ config("app.url") }}'.replace(/\/$/, '');
    </script>
    
    <script>
    // Wait for QR code libraries to load
    (function() {
        let attempts = 0;
        const maxAttempts = 50; // 5 seconds max wait
        
        function checkLibraries() {
            attempts++;
            // Check if QRCode is available (either as global or window.QRCode)
            const qrCodeAvailable = typeof QRCode !== 'undefined' || typeof window.QRCode !== 'undefined';
            const handlerAvailable = typeof window.QRCodeHandler !== 'undefined';
            
            if (qrCodeAvailable && handlerAvailable) {
                // Make sure QRCode is on window if it's not already
                if (typeof window.QRCode === 'undefined' && typeof QRCode !== 'undefined') {
                    window.QRCode = QRCode;
                }
                console.log('QR code libraries loaded successfully');
                return;
            }
            
            if (attempts < maxAttempts) {
                setTimeout(checkLibraries, 100);
            } else {
                console.error('QR code libraries failed to load after', maxAttempts * 100, 'ms');
                console.log('QRCode available:', qrCodeAvailable);
                console.log('QRCodeHandler available:', handlerAvailable);
            }
        }
        
        // Start checking after a short delay to allow scripts to load
        setTimeout(checkLibraries, 100);
    })();
    </script>
</body>
</html>


