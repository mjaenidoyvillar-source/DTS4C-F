<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archived - Owner - Document Tracking System</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
</head>
<body>
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
                        <button class="nav-btn" data-page="dashboard" onclick="navigateTo('dashboard')"><i class="bi bi-grid-fill" style="font-size: 24px;"></i><span>Dashboard</span></button>
                        <button class="nav-btn" data-page="my-docs" onclick="navigateTo('my-docs')"><i class="bi bi-file-earmark-text" style="font-size: 24px;"></i><span>My Documents</span></button>
                    </div>
                </div>
                <div class="nav-section">
                    <p class="nav-section-title">OUTGOING DOCUMENTS</p>
                    <div class="nav-menu">
                        <button class="nav-btn" data-page="released" onclick="navigateTo('released')"><i class="bi bi-send" style="font-size: 18px;"></i><span>Released</span></button>
                        <button class="nav-btn" data-page="for-review" onclick="navigateTo('for-review')"><i class="bi bi-clipboard-check" style="font-size: 18px;"></i><span>For Review</span></button>
                        <button class="nav-btn" data-page="complete" onclick="navigateTo('complete')"><i class="bi bi-check-circle" style="font-size: 18px;"></i><span>Complete</span></button>
                        <button class="nav-btn active" data-page="archived" onclick="navigateTo('archived')"><i class="bi bi-archive" style="font-size: 18px;"></i><span>Archived</span></button>
                        <button class="nav-btn" data-page="deleted" onclick="navigateTo('deleted')"><i class="bi bi-trash" style="font-size: 18px;"></i><span>Deleted</span></button>
                    </div>
                </div>
            </div>
            <div class="sidebar-user">
                <div class="dropdown w-100">
                    <button class="user-info w-100 bg-transparent border-0 text-start d-flex align-items-center" id="userMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="user-avatar"><img src="{{ Auth::user()->profile_picture_url ?? 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . (Auth::id() ?? 0) }}" alt="{{ $user->name ?? 'User' }} avatar"></div>
                        <div class="user-details"><p class="user-name">{{ $user->name ?? 'User' }}</p><p class="user-department">Owner</p></div>
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
            </div>
            <div class="content-body">
                <h1 class="page-title">ARCHIVED DOCUMENTS</h1>
                <div class="documents-table-container">
                    <table class="documents-table">
                        <thead>
                            <tr>
                                <th>Code</th><th>Type</th><th>Description</th><th>Purpose</th><th>Receiving Department</th><th>Archived Date</th><th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($documents as $doc)
                            <tr>
                                <td>#{{ $doc->id }}</td>
                                <td>{{ $doc->document_type }}</td>
                                <td>{{ \Illuminate\Support\Str::limit($doc->description, 60) }}</td>
                                <td>{{ $doc->purpose }}</td>
                                <td>{{ optional($doc->receiving_department_id ? \App\Models\Department::find($doc->receiving_department_id) : null)->name }}</td>
                                <td>{{ $doc->updated_at->format('Y-m-d H:i') }}</td>
                                <td>
                                    <button class="btn btn-sm btn-outline-info" onclick="viewDocumentModal({{ $doc->id }})" title="View Details">
                                        <i class="bi bi-info-circle"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-success me-1" onclick="unarchiveDocument({{ $doc->id }}, '{{ addslashes($doc->title) }}')" title="Unarchive">
                                        <i class="bi bi-archive"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteDocument({{ $doc->id }}, '{{ addslashes($doc->title) }}')" title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="7" class="text-center">No archived documents found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-3 d-flex justify-content-center">{{ $documents->links() }}</div>
            </div>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div class="modal fade" id="confirmationModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmationModalTitle">Confirm Action</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="confirmationModalBody">
                    <p id="confirmationModalMessage">Are you sure you want to proceed?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirmationModalConfirm">Confirm</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('jss/dashboard.js') }}"></script>
    <script>
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
                const fileSize = doc.file_size ? (doc.file_size < 1024 ? doc.file_size + ' bytes' : doc.file_size < 1048576 ? (doc.file_size / 1024).toFixed(2) + ' KB' : (doc.file_size / 1048576).toFixed(2) + ' MB') : '—';
                contentDiv.innerHTML = `<div class="mb-4"><h4>${doc.title || 'Document'}</h4><p class="text-muted"><i class="bi bi-file-earmark"></i> Document Code: #${doc.id}</p></div><div class="row"><div class="col-md-6 mb-3"><strong>Document Type:</strong><br><span>${doc.document_type || '—'}</span></div><div class="col-md-6 mb-3"><strong>Status:</strong><br><span class="badge bg-secondary">${doc.current_status || 'Archived'}</span></div><div class="col-md-6 mb-3"><strong>Purpose:</strong><br><span>${doc.purpose || '—'}</span></div><div class="col-md-6 mb-3"><strong>Created Date:</strong><br><span>${doc.created_at || '—'}</span></div><div class="col-md-6 mb-3"><strong>Department:</strong><br><span>${doc.department || '—'}</span></div><div class="col-md-6 mb-3"><strong>Receiving Department:</strong><br><span>${doc.receiving_department || '—'}</span></div></div><div class="mb-3"><strong>Description:</strong><div class="mt-2 p-3 bg-light border-start border-primary border-4 rounded">${doc.description || 'No description provided.'}</div></div>${doc.has_file ? `<div class="p-4 bg-info bg-opacity-10 border border-info rounded mt-4"><h5 class="mb-3"><i class="bi bi-file-earmark-text"></i> File Information</h5><div class="row"><div class="col-md-6 mb-3"><strong>File Name:</strong><br><span>${doc.file_name || '—'}</span></div><div class="col-md-6 mb-3"><strong>File Type:</strong><br><span>${doc.file_mime || '—'}</span></div>${doc.file_size ? `<div class="col-md-6 mb-3"><strong>File Size:</strong><br><span>${fileSize}</span></div>` : ''}</div><div class="text-center mt-3"><p class="text-muted mb-0">Download the file to your device to view its contents</p></div></div>` : '<div class="alert alert-warning mt-4"><i class="bi bi-exclamation-triangle"></i> No file attached.</div>'}`;
                if (doc.has_file) {
                    downloadLink.href = `/documents/${docId}/file`;
                    downloadLink.style.display = 'inline-block';
                }
            } catch (error) {
                console.error(error);
                contentDiv.innerHTML = '<div class="alert alert-danger">Failed to load document details.</div>';
            }
        }

        // Show confirmation modal
        let currentConfirmHandler = null;
        function showConfirmationModal(title, message, confirmCallback, confirmText = 'Confirm', confirmClass = 'btn-primary') {
            const modalEl = document.getElementById('confirmationModal');
            const modal = new bootstrap.Modal(modalEl);
            
            document.getElementById('confirmationModalTitle').textContent = title;
            document.getElementById('confirmationModalMessage').textContent = message;
            const confirmBtn = document.getElementById('confirmationModalConfirm');
            confirmBtn.textContent = confirmText;
            confirmBtn.className = `btn ${confirmClass}`;
            
            // Remove previous event listener if exists
            if (currentConfirmHandler) {
                confirmBtn.removeEventListener('click', currentConfirmHandler);
            }
            
            // Create new handler
            currentConfirmHandler = function() {
                modal.hide();
                if (confirmCallback) confirmCallback();
            };
            
            // Add new listener
            confirmBtn.addEventListener('click', currentConfirmHandler);
            
            // Show modal - Bootstrap will handle aria-hidden automatically
            modal.show();
        }

        async function unarchiveDocument(docId, docTitle) {
            showConfirmationModal(
                'Unarchive Document',
                `Unarchive document "${docTitle}"? It will be restored to Complete status.`,
                async () => {
                    try {
                        const response = await fetch(`/documents/${docId}/unarchive`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                'Accept': 'application/json'
                            }
                        });
                        
                        const data = await response.json();
                        
                        if (response.ok) {
                            showToast('Document unarchived successfully - redirecting to Complete');
                            setTimeout(() => {
                                window.location.href = '/owner/complete';
                            }, 1000);
                        } else {
                            showToast(data.message || 'Failed to unarchive document', 'error');
                        }
                    } catch (error) {
                        console.error(error);
                        showToast('An error occurred while unarchiving the document', 'error');
                    }
                },
                'Unarchive',
                'btn-success'
            );
        }

        async function deleteDocument(docId, docTitle) {
            showConfirmationModal(
                'Delete Document',
                `Delete document "${docTitle}" permanently? This action cannot be undone.`,
                async () => {
                    try {
                        const response = await fetch(`/documents/${docId}`, {
                            method: 'DELETE',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                'Accept': 'application/json'
                            }
                        });
                        
                        if (response.ok) {
                            showToast('Document deleted successfully');
                            setTimeout(() => location.reload(), 500);
                        } else {
                            const data = await response.json();
                            showToast(data.message || 'Failed to delete document', 'error');
                        }
                    } catch (error) {
                        console.error(error);
                        showToast('An error occurred while deleting the document', 'error');
                    }
                },
                'Delete',
                'btn-danger'
            );
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
        
        try {
            const response = await fetch('/profile');
            const data = await response.json();
            
            document.getElementById('profileName').value = data.user.name || '';
            
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
                
                if (data.user && data.user.profile_picture) {
                    const avatarImages = document.querySelectorAll('.user-avatar img');
                    avatarImages.forEach(img => {
                        img.src = data.user.profile_picture + '?t=' + new Date().getTime();
                    });
                }
                
                // Reset button state on success
                submitButton.disabled = false;
                submitButton.innerHTML = originalButtonText;
                
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
</body>
</html>

