<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>To Process - Handler - Document Tracking System</title>
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
                    </div>
                </div>
                <div class="nav-section">
                    <p class="nav-section-title">DOCUMENTS</p>
                    <div class="nav-menu">
                        <button class="nav-btn active" data-page="to-process" onclick="navigateTo('to-process')"><i class="bi bi-file-earmark-text" style="font-size: 18px;"></i><span>To Process</span></button>
                        <button class="nav-btn" data-page="on-hold" onclick="navigateTo('on-hold')"><i class="bi bi-pause-circle" style="font-size: 18px;"></i><span>On Hold</span></button>
                    </div>
                </div>
                <div class="nav-section">
                    <p class="nav-section-title">OUTGOING DOCUMENTS</p>
                    <div class="nav-menu">
                        <button class="nav-btn" data-page="outgoing" onclick="navigateTo('outgoing')"><i class="bi bi-send" style="font-size: 18px;"></i><span>Outgoing</span></button>
                    </div>
                </div>
            </div>
            <div class="sidebar-user">
                <div class="dropdown w-100">
                    <button class="user-info w-100 bg-transparent border-0 text-start d-flex align-items-center" id="userMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="user-avatar"><img src="{{ Auth::user()->profile_picture_url ?? 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . (Auth::id() ?? 0) }}" alt="{{ $user->name ?? 'User' }} avatar"></div>
                        <div class="user-details"><p class="user-name">{{ $user->name ?? 'User' }}</p><p class="user-department">Handler</p></div>
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
                <h1 class="page-title">TO PROCESS</h1>
                <div class="alert alert-info mb-3">
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>Document Status:</strong>
                    <br><small class="text-muted">
                    • <span class="badge bg-warning text-dark">Pending</span> - Documents sent from other departments (can forward directly to owner)<br>
                    • <span class="badge bg-success">Received</span> - Documents received from other departments (forward to your department owner)<br>
                    • <strong>Forward to Owner:</strong> Send documents to your department's owner for review (available for pending or received documents)<br>
                    • <strong>Send:</strong> Only available for documents from your own department owner (sends to other departments)
                    </small>
                </div>
                <div class="documents-table-container">
                    <table class="documents-table">
                        <thead>
                            <tr>
                                <th>Code</th><th>Type</th><th>Description</th><th>Purpose</th><th>Receiving Department</th><th>Status</th><th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($documents as $doc)
                            <tr class="@if(isset($doc->is_received) && $doc->is_received) table-success @elseif(isset($doc->is_pending_receipt) && $doc->is_pending_receipt) table-warning @endif">
                                <td>#{{ $doc->id }}</td>
                                <td>{{ $doc->document_type }}</td>
                                <td>
                                    {{ \Illuminate\Support\Str::limit($doc->description, 60) }}
                                    @if(isset($doc->is_received) && $doc->is_received)
                                        <span class="badge bg-success ms-2" title="Received - Ready to forward to owner">Received</span>
                                    @elseif(isset($doc->is_pending_receipt) && $doc->is_pending_receipt)
                                        <span class="badge bg-warning ms-2" title="Pending Receipt - Check Incoming page to receive">Pending</span>
                                    @endif
                                </td>
                                <td>{{ $doc->purpose }}</td>
                                <td>{{ optional($doc->receiving_department_id ? \App\Models\Department::find($doc->receiving_department_id) : null)->name }}</td>
                                <td>{{ $doc->current_status }}</td>
                                <td>
                                    <button class="btn btn-sm btn-outline-info me-1" onclick="viewDocumentModal({{ $doc->id }})" title="View Document"><i class="bi bi-eye"></i></button>
                                    @if(isset($doc->is_received) && $doc->is_received)
                                        {{-- Received from another handler: Forward to department owner, then Hold --}}
                                        <button class="btn btn-sm btn-success me-1" onclick="forwardDocumentToOwner({{ $doc->id }})" title="Forward to Owner"><i class="bi bi-forward"></i></button>
                                        <button class="btn btn-sm btn-outline-warning" onclick="holdDocument({{ $doc->id }})" title="Hold"><i class="bi bi-pause-circle"></i></button>
                                    @elseif(isset($doc->is_pending_receipt) && $doc->is_pending_receipt)
                                        {{-- Pending receipt: Can forward directly to owner (will auto-receive) or hold --}}
                                        <button class="btn btn-sm btn-success me-1" onclick="forwardDocumentToOwner({{ $doc->id }})" title="Forward to Owner"><i class="bi bi-forward"></i></button>
                                        <button class="btn btn-sm btn-outline-warning" onclick="holdDocument({{ $doc->id }})" title="Hold"><i class="bi bi-pause-circle"></i></button>
                                    @else
                                        {{-- Document from own department owner meant for different department: Send to other department or Hold --}}
                                        <button class="btn btn-sm btn-outline-primary me-1" data-bs-toggle="modal" data-bs-target="#sendDocumentModal" data-document-id="{{ $doc->id }}" title="Send to Other Department"><i class="bi bi-send"></i></button>
                                        <button class="btn btn-sm btn-outline-warning" onclick="holdDocument({{ $doc->id }})" title="Hold"><i class="bi bi-pause-circle"></i></button>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="7" class="text-center">No documents to process</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-3 d-flex justify-content-center">{{ $documents->links() }}</div>
            </div>
        </div>
    </div>

    <!-- Send Document Modal -->
    <div class="modal fade" id="sendDocumentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog"><div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Send Document</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
            <form method="POST" id="sendDocumentForm">@csrf
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        This document will be sent to the department and owner specified by the document creator.
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Receiving Department</label>
                        <input type="text" class="form-control" id="receivingDeptDisplay" readonly style="background-color: #e9ecef;" />
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Target Owner</label>
                        <input type="text" class="form-control" id="targetOwnerDisplay" readonly style="background-color: #e9ecef;" />
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary"><i class="bi bi-send me-1"></i>Send</button></div>
            </form>
        </div></div>
    </div>

    <!-- Send To Owner Modal -->
    <div class="modal fade" id="sendToOwnerModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog"><div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Send Document to Owner</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
            <form method="POST" id="sendToOwnerForm">@csrf
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        Select an owner in your department to send this document directly to them.
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Select Owner <span class="text-danger">*</span></label>
                        <select class="form-control" id="ownerSelect" name="owner_id" required>
                            <option value="" disabled selected>Loading owners...</option>
                        </select>
                        <small class="form-text text-muted">Choose an owner from your department</small>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-success"><i class="bi bi-person-check me-1"></i>Send to Owner</button></div>
            </form>
        </div></div>
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
            contentDiv.innerHTML = `<div class="mb-4"><h4>${doc.title || 'Document'}</h4><p class="text-muted"><i class="bi bi-file-earmark"></i> Document Code: #${doc.id}</p></div><div class="row"><div class="col-md-6 mb-3"><strong>Document Type:</strong><br><span>${doc.document_type || '—'}</span></div><div class="col-md-6 mb-3"><strong>Status:</strong><br><span class="badge bg-primary">${doc.current_status || '—'}</span></div><div class="col-md-6 mb-3"><strong>Purpose:</strong><br><span>${doc.purpose || '—'}</span></div><div class="col-md-6 mb-3"><strong>Created Date:</strong><br><span>${doc.created_at || '—'}</span></div><div class="col-md-6 mb-3"><strong>Department:</strong><br><span>${doc.department || '—'}</span></div><div class="col-md-6 mb-3"><strong>Receiving Department:</strong><br><span>${doc.receiving_department || '—'}</span></div></div><div class="mb-3"><strong>Description:</strong><div class="mt-2 p-3 bg-light border-start border-primary border-4 rounded">${doc.description || 'No description provided.'}</div></div>${doc.has_file ? `<div class="p-4 bg-info bg-opacity-10 border border-info rounded mt-4"><h5 class="mb-3"><i class="bi bi-file-earmark-text"></i> File Information</h5><div class="row"><div class="col-md-6 mb-3"><strong>File Name:</strong><br><span>${doc.file_name || '—'}</span></div><div class="col-md-6 mb-3"><strong>File Type:</strong><br><span>${doc.file_mime || '—'}</span></div>${doc.file_size ? `<div class="col-md-6 mb-3"><strong>File Size:</strong><br><span>${fileSize}</span></div>` : ''}</div><div class="text-center mt-3"><p class="text-muted mb-0">Download the file to your device to view its contents</p></div></div>` : '<div class="alert alert-warning mt-4"><i class="bi bi-exclamation-triangle"></i> No file attached.</div>'}`;
            if (doc.has_file) {
                downloadLink.href = `/documents/${docId}/file`;
                downloadLink.style.display = 'inline-block';
            }
        } catch (error) {
            console.error(error);
            contentDiv.innerHTML = '<div class="alert alert-danger">Failed to load document details.</div>';
        }
    }

    const sendModal = document.getElementById('sendDocumentModal');
    if (sendModal) {
        sendModal.addEventListener('show.bs.modal', async function (event) {
            const button = event.relatedTarget;
            const docId = button?.getAttribute('data-document-id');
            const form = document.getElementById('sendDocumentForm');
            if (form && docId) {
                form.setAttribute('action', `/documents/${docId}/send`);
                // Fetch document details to get receiving department and target owner
                try {
                    const response = await fetch(`/documents/${docId}/details`);
                    if (!response.ok) {
                        throw new Error('Failed to fetch document details');
                    }
                    const doc = await response.json();
                    
                    // Set receiving department
                    document.getElementById('receivingDeptDisplay').value = doc.receiving_department || 'Not set';
                    
                    // Use target_owner from document details (already included in response)
                    if (doc.target_owner) {
                        document.getElementById('targetOwnerDisplay').value = doc.target_owner;
                    } else if (doc.target_owner_id) {
                        // If target_owner name is not in response, try to fetch it
                        try {
                            const ownerResponse = await fetch(`/api/users/${doc.target_owner_id}`, {
                                headers: {
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                                },
                                credentials: 'same-origin'
                            });
                            if (ownerResponse.ok) {
                                const owner = await ownerResponse.json();
                                document.getElementById('targetOwnerDisplay').value = owner.name || owner.email || 'Unknown';
                            } else {
                                document.getElementById('targetOwnerDisplay').value = 'Owner ID: ' + doc.target_owner_id;
                            }
                        } catch (err) {
                            console.error('Failed to fetch owner details', err);
                            document.getElementById('targetOwnerDisplay').value = 'Owner ID: ' + doc.target_owner_id;
                        }
                    } else {
                        document.getElementById('targetOwnerDisplay').value = 'Not specified';
                    }
                } catch (err) {
                    console.error('Failed to load document details', err);
                    document.getElementById('receivingDeptDisplay').value = 'Error loading';
                    document.getElementById('targetOwnerDisplay').value = 'Error loading';
                }
            }
        });
        const form = document.getElementById('sendDocumentForm');
        if (form) form.addEventListener('submit', async function(e){
            e.preventDefault();
            // No payload needed - handler just sends to pre-specified department/owner
            try { 
                await postJson(form.getAttribute('action'), {}); 
                showToast('Document sent'); 
                location.reload(); 
            } catch (err) { 
                showToast('Failed to send: ' + (err.message || 'Unknown error'), 'error'); 
            }
        });
    }

    // Send To Owner Modal Handler
    const sendToOwnerModal = document.getElementById('sendToOwnerModal');
    if (sendToOwnerModal) {
        sendToOwnerModal.addEventListener('show.bs.modal', async function (event) {
            const button = event.relatedTarget;
            const docId = button?.getAttribute('data-document-id');
            const deptId = button?.getAttribute('data-department-id');
            const form = document.getElementById('sendToOwnerForm');
            const ownerSelect = document.getElementById('ownerSelect');
            
            if (form && docId && deptId) {
                form.setAttribute('action', `/documents/${docId}/send-to-owner`);
                ownerSelect.innerHTML = '<option value="" disabled>Loading owners...</option>';
                ownerSelect.disabled = true;
                
                // Load owners from department
                try {
                    const response = await fetch(`/api/departments/${deptId}/owners`, {
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        credentials: 'same-origin'
                    });
                    
                    if (!response.ok) {
                        throw new Error('Failed to load owners');
                    }
                    
                    const owners = await response.json();
                    ownerSelect.innerHTML = '<option value="" disabled selected>Select Owner</option>';
                    
                    if (owners.length === 0) {
                        ownerSelect.innerHTML += '<option value="" disabled>No owners found in this department</option>';
                    } else {
                        owners.forEach(owner => {
                            const displayName = owner.name || owner.email || 'Unknown';
                            ownerSelect.innerHTML += `<option value="${owner.id}">${displayName}</option>`;
                        });
                    }
                    
                    ownerSelect.disabled = false;
                } catch (err) {
                    console.error('Failed to load owners', err);
                    ownerSelect.innerHTML = '<option value="" disabled>Error loading owners</option>';
                    ownerSelect.disabled = true;
                }
            }
        });
        
        const sendToOwnerForm = document.getElementById('sendToOwnerForm');
        if (sendToOwnerForm) {
            sendToOwnerForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                const ownerId = formData.get('owner_id');
                
                if (!ownerId) {
                    showToast('Please select an owner', 'error');
                    return;
                }
                
                try {
                    await postJson(this.getAttribute('action'), { owner_id: ownerId });
                    showToast('Document sent to owner successfully');
                    location.reload();
                } catch (err) {
                    showToast('Failed to send: ' + (err.message || 'Unknown error'), 'error');
                }
            });
        }
    }

    // Show input modal
    function showInputModal(title, message, inputLabel, inputPlaceholder, confirmCallback, confirmText = 'Confirm', confirmClass = 'btn-primary') {
        const modalEl = document.getElementById('inputModal');
        if (!modalEl) {
            const modalHtml = `<div class="modal fade" id="inputModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="inputModalTitle">Input Required</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p id="inputModalMessage"></p>
                            <div class="mb-3">
                                <label for="inputModalInput" class="form-label" id="inputModalLabel"></label>
                                <input type="text" class="form-control" id="inputModalInput" placeholder="">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary" id="inputModalConfirm">Confirm</button>
                        </div>
                    </div>
                </div>
            </div>`;
            document.body.insertAdjacentHTML('beforeend', modalHtml);
        }
        
        const modal = new bootstrap.Modal(document.getElementById('inputModal'));
        document.getElementById('inputModalTitle').textContent = title;
        document.getElementById('inputModalMessage').textContent = message;
        document.getElementById('inputModalLabel').textContent = inputLabel;
        const inputField = document.getElementById('inputModalInput');
        inputField.placeholder = inputPlaceholder;
        inputField.value = '';
        
        const confirmBtn = document.getElementById('inputModalConfirm');
        confirmBtn.textContent = confirmText;
        confirmBtn.className = `btn ${confirmClass}`;
        
        // Remove existing listeners and add new one
        const newConfirmBtn = confirmBtn.cloneNode(true);
        confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);
        
        newConfirmBtn.addEventListener('click', function() {
            const value = inputField.value;
            modal.hide();
            if (confirmCallback) confirmCallback(value);
        });
        
        // Clear input when modal is hidden
        document.getElementById('inputModal').addEventListener('hidden.bs.modal', function() {
            inputField.value = '';
        }, { once: true });
        
        modal.show();
        inputField.focus();
    }

    async function forwardDocumentToOwner(docId) {
        try {
            await postJson(`/documents/${docId}/forward-to-owner`, {});
            showToast('Document forwarded to owner');
            location.reload();
        } catch (err) {
            showToast('Failed to forward document: ' + (err.message || 'Unknown error'), 'error');
        }
    }

    async function holdDocument(docId) {
        showInputModal(
            'Hold Document',
            'Please provide a reason for placing this document on hold (optional):',
            'Reason',
            'Enter reason (optional)',
            async (reason) => {
                try {
                    await postJson(`/documents/${docId}/hold`, { reason: reason || '' });
                    showToast('Document placed on hold');
                    location.reload();
                } catch (err) {
                    showToast('Failed to hold document', 'error');
                }
            },
            'Hold',
            'btn-warning'
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

