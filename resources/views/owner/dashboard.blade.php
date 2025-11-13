<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Owner Dashboard - Document Tracking System</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
    <style>
        .nav-section-title{margin-top:0}
        
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
        
        /* Improved spacing for received table */
        #receivedTable td,
        #receivedTable th {
            padding: 0.75rem 1rem;
            vertical-align: middle;
        }
        
        #receivedTable th {
            font-weight: 600;
            padding: 1rem;
        }
        
        /* Improved spacing for archived table */
        #archivedTable td,
        #archivedTable th {
            padding: 0.75rem 1rem;
            vertical-align: middle;
        }
        
        #archivedTable th {
            font-weight: 600;
            padding: 1rem;
        }
        
        /* Action buttons spacing */
        #receivedTable .d-flex.gap-2,
        #archivedTable .d-flex.gap-2 {
            gap: 0.5rem !important;
        }
        
        #receivedTable .btn-sm,
        #archivedTable .btn-sm {
            padding: 0.5rem 0.75rem;
            min-width: 40px;
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
                        <button class="nav-btn" data-page="incoming" onclick="navigateTo('incoming')">
                            <i class="bi bi-inbox" style="font-size: 22px;"></i><span>Incoming</span>
                        </button>
                        <button class="nav-btn" data-page="received" onclick="navigateTo('received')">
                            <i class="bi bi-check2-square" style="font-size: 22px;"></i><span>Received</span>
                        </button>
                    </div>
                </div>
                <!-- Removed extra nav sections to keep only Dashboard and Incoming -->
            </div>
            <div class="sidebar-user">
                <div class="dropdown w-100">
                    <button class="user-info w-100 bg-transparent border-0 text-start d-flex align-items-center" id="userMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="user-avatar"><img src="{{ Auth::user()->profile_picture_url ?? 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . (Auth::id() ?? 0) }}" alt="{{ $user->name ?? 'User' }} avatar"></div>
                        <div class="user-details">
                            <p class="user-name">{{ $user->name ?? 'User' }}</p>
                            <p class="user-department">Owner</p>
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
                <button class="new-doc-btn me-2" data-bs-toggle="modal" data-bs-target="#scanQRModal" onclick="initQRScanner()">
                    <i class="bi bi-qr-code-scan" style="font-size: 18px;"></i> Scan QR Code
                </button>
                <button class="new-doc-btn" data-bs-toggle="modal" data-bs-target="#createDocumentModal">
                    <i class="bi bi-file-earmark-plus" style="font-size: 18px;"></i> New Document
                </button>
            </div>

            <div class="content-body">
                <h1 class="page-title">DOCUMENT TRACKING SYSTEM</h1>
                <div class="stats-grid" id="stats">
                    <div class="stat-card stat-blue" onclick="navigateTo('dashboard')">
                        <div class="stat-number">{{ $stats['totalSent'] ?? 0 }}</div>
                        <div class="stat-label">TOTAL SENT</div>
                    </div>
                    <div class="stat-card stat-green" onclick="navigateTo('received')">
                        <div class="stat-number">{{ $stats['totalReceived'] ?? 0 }}</div>
                        <div class="stat-label">TOTAL RECEIVED</div>
                    </div>
                    <div class="stat-card stat-yellow" onclick="navigateTo('incoming')">
                        <div class="stat-number">{{ $stats['incoming'] ?? 0 }}</div>
                        <div class="stat-label">INCOMING</div>
                    </div>
                    <div class="stat-card stat-red" onclick="navigateTo('dashboard')">
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
                                    <th>Title</th><th>Document Type</th><th>Description</th><th>Purpose</th><th>Direction</th><th>Sender/Recipient</th><th>Department</th><th>Date</th><th>File</th><th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse(($recentDocuments ?? []) as $doc)
                                @php
                                    $isSent = $doc->owner_id == Auth::id();
                                    $isReceived = ($doc->current_owner_id == Auth::id() || $doc->target_owner_id == Auth::id()) && !$isSent;
                                @endphp
                                <tr>
                                    <td>{{ $doc->title }}</td>
                                    <td>{{ $doc->document_type }}</td>
                                    <td>{{ \Illuminate\Support\Str::limit($doc->description, 60) }}</td>
                                    <td>{{ $doc->purpose }}</td>
                                    <td>
                                        @if($isSent)
                                            <span class="doc-status-badge doc-status-sent">Sent</span>
                                        @elseif($isReceived)
                                            <span class="doc-status-badge doc-status-received">Received</span>
                                        @else
                                            <span class="doc-status-badge doc-status-default">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($isSent)
                                            <strong>Recipient:</strong> {{ $doc->targetOwner->name ?? $doc->targetOwner->email ?? '—' }}
                                        @elseif($isReceived)
                                            <strong>Sender:</strong> {{ $doc->owner->name ?? $doc->owner->email ?? '—' }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>
                                        @if($isSent)
                                            {{ optional($doc->receivingDepartment)->name ?? '—' }}
                                        @elseif($isReceived)
                                            {{ optional($doc->owner->department)->name ?? '—' }}
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>{{ $doc->created_at->format('Y-m-d H:i') }}</td>
                                    <td>@if($doc->file_data || $doc->id)<button class="btn btn-sm btn-outline-info" onclick="viewDocumentModal({{ $doc->id }})" title="View"><i class="bi bi-eye"></i></button>@else — @endif</td>
                                    <td>
                                        <span class="doc-status-badge doc-status-{{ strtolower(str_replace(['_', ' '], '-', $doc->current_status)) }}">
                                            {{ ucwords(str_replace(['_', '-'], ' ', $doc->current_status)) }}
                                        </span>
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="10" class="text-center">No documents yet</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div id="incoming-list" style="display:none;">
                    <h2 class="recent-section-title">Incoming Documents</h2>
                    <div class="documents-table-container">
                        <table class="documents-table" id="incomingTable">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Type</th>
                                    <th>Description</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr><td colspan="5" class="text-center py-4">Loading...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div id="received-list" style="display:none;">
                    <h2 class="recent-section-title">My Documents</h2>
                    <!-- Tabs for received and archived documents -->
                    <ul class="nav nav-tabs mb-3" id="documentsTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="received-tab" data-bs-toggle="tab" data-bs-target="#received-pane" type="button" role="tab" aria-controls="received-pane" aria-selected="true" onclick="attachReceivedFilters(); loadReceived();">Received Documents</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="archived-tab" data-bs-toggle="tab" data-bs-target="#archived-pane" type="button" role="tab" aria-controls="archived-pane" aria-selected="false" onclick="loadArchived()">Archived Documents</button>
                        </li>
                    </ul>
                    <div class="tab-content" id="documentsTabContent">
                        <!-- Received Documents Tab -->
                        <div class="tab-pane fade show active" id="received-pane" role="tabpanel" aria-labelledby="received-tab">
                            <!-- Filter Section -->
                            <div class="d-flex justify-content-end align-items-center mb-3" style="gap: 0.5rem;">
                                <input type="text" id="receivedSearch" placeholder="Search..." class="form-control form-control-sm" style="font-size: 0.8125rem; padding: 0.375rem 0.5rem; width: 200px;" />
                                <select id="receivedDocumentType" class="form-select form-select-sm" style="font-size: 0.8125rem; padding: 0.375rem 0.5rem; width: 150px;">
                                    <option value="">All Types</option>
                                    <option value="DOC">DOC</option>
                                    <option value="DOCX">DOCX</option>
                                    <option value="PDF">PDF</option>
                                    <option value="XLS">XLS</option>
                                    <option value="XLSX">XLSX</option>
                                    <option value="PPT">PPT</option>
                                    <option value="PPTX">PPTX</option>
                                    <option value="TXT">TXT</option>
                                    <option value="CSV">CSV</option>
                                    <option value="RTF">RTF</option>
                                    <option value="ODT">ODT</option>
                                </select>
                                <select id="receivedDepartment" class="form-select form-select-sm" style="font-size: 0.8125rem; padding: 0.375rem 0.5rem; width: 180px;">
                                    <option value="">All Departments</option>
                                    @foreach($departments as $dept)
                                        <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                                    @endforeach
                                </select>
                                <button type="button" id="clearReceivedFiltersBtn" class="btn btn-outline-secondary btn-sm" onclick="clearReceivedFilters()" style="display: none; font-size: 0.8125rem; padding: 0.375rem 0.5rem;">
                                    <i class="bi bi-x-circle me-1"></i>Clear
                                </button>
                            </div>
                            <div class="documents-table-container">
                                <table class="documents-table" id="receivedTable">
                                    <thead>
                                        <tr>
                                            <th>Title</th>
                                            <th>Document Type</th>
                                            <th>Description</th>
                                            <th>Purpose</th>
                                            <th>Direction</th>
                                            <th>Sender/Recipient</th>
                                            <th>Department</th>
                                            <th>Date</th>
                                            <th>File</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr><td colspan="11" class="text-center py-4">Loading...</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <!-- Archived Documents Tab -->
                        <div class="tab-pane fade" id="archived-pane" role="tabpanel" aria-labelledby="archived-tab">
                            <div class="documents-table-container">
                                <table class="documents-table" id="archivedTable">
                                    <thead>
                                        <tr>
                                            <th>Title</th>
                                            <th>Document Type</th>
                                            <th>Description</th>
                                            <th>Purpose</th>
                                            <th>Direction</th>
                                            <th>Sender/Recipient</th>
                                            <th>Department</th>
                                            <th>Date</th>
                                            <th>File</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr><td colspan="11" class="text-center py-4">Loading...</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Document Modal -->
    <div class="modal fade" id="createDocumentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog"><div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Create Document</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
            <form method="POST" action="{{ route('documents.register') }}" enctype="multipart/form-data" id="createDocumentForm">@csrf
                <div class="modal-body">
                    @if($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <strong>Error:</strong>
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif
                    @if(session('status'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('status') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif
                    <div class="mb-3">
                        <label class="form-label">Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title') }}" required />
                        @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="3">{{ old('description') }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Purpose <span class="text-danger">*</span></label>
                        <input type="text" name="purpose" class="form-control @error('purpose') is-invalid @enderror" value="{{ old('purpose') }}" required />
                        @error('purpose')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Receiving Department <span class="text-danger">*</span></label>
                        <select name="receiving_department_id" id="receivingDepartmentSelect" class="form-select @error('receiving_department_id') is-invalid @enderror" required onchange="loadTargetOwners(this.value)">
                            <option value="" selected disabled>Select department</option>
                            @foreach(($departments ?? []) as $dept)
                                <option value="{{ $dept->id }}" {{ old('receiving_department_id') == $dept->id ? 'selected' : '' }}>
                                    {{ $dept->name }}
                                    @if($dept->id == ($user->department_id ?? null))
                                        (Same Department - Direct to Owner)
                                    @endif
                                </option>
                            @endforeach
                        </select>
                        @error('receiving_department_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">Select any department. Same department sends directly to owner (bypasses handler).</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Target Owner in Receiving Department <span class="text-danger">*</span></label>
                        <select name="target_owner_id" id="targetOwnerSelect" class="form-select @error('target_owner_id') is-invalid @enderror" required>
                            <option value="" selected disabled>Select department first</option>
                        </select>
                        @error('target_owner_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">Specify which owner in the receiving department should receive this document</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">File <span class="text-danger">*</span></label>
                        <input type="file" name="file" id="fileInput" class="form-control @error('file') is-invalid @enderror" required accept=".doc,.docx,.pdf,.xls,.xlsx,.ppt,.pptx,.txt,.csv,.rtf,.odt" />
                        @error('file')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                        <div id="fileError" class="invalid-feedback d-none"></div>
                        <small class="form-text text-muted">Supported formats: DOC, DOCX, PDF, XLS, XLSX, PPT, PPTX, TXT, CSV, RTF, ODT (Max size: 50MB)</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Document Type <span class="text-danger">*</span></label>
                        <input type="text" name="document_type" id="documentTypeInput" class="form-control @error('document_type') is-invalid @enderror" value="{{ old('document_type') }}" required readonly style="background-color: #e9ecef;" />
                        @error('document_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">Document type is automatically detected from the uploaded file.</small>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary"><i class="bi bi-plus-circle me-1"></i>Create</button></div>
            </form>
        </div></div>
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
            "></script>
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js" onerror="console.error('Failed to load Html5Qrcode library')"></script>
    <script src="{{ asset('js/qrcode-handler.js') }}" onerror="console.error('Failed to load QRCodeHandler')"></script>
    
    <script>
    // Set app URL for QR code generation (ensures QR codes work in production)
    window.APP_URL = '{{ config("app.url") }}'.replace(/\/$/, '');
    </script>
    
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

    // Load owners when department is selected
    // Loads owners from the RECEIVING department (can be same or different from creator's department)
    async function loadTargetOwners(deptId, selectedOwnerId = null) {
        const ownerSelect = document.getElementById('targetOwnerSelect');
        const creatorDeptId = {{ $user->department_id ?? 'null' }};
        const creatorId = {{ $user->id ?? 'null' }};
        
        ownerSelect.innerHTML = '<option value="">Loading...</option>';
        ownerSelect.disabled = true;
        
        if (!deptId) {
            ownerSelect.innerHTML = '<option value="" selected disabled>Select department first</option>';
            return;
        }
        
        try {
            const response = await fetch(`/api/departments/${deptId}/owners`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                },
                credentials: 'same-origin'
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const owners = await response.json();
            ownerSelect.innerHTML = '<option value="" selected disabled>Select Owner</option>';
            if (owners.length === 0) {
                ownerSelect.innerHTML += '<option value="" disabled>No owners found in this department</option>';
            } else {
                owners.forEach(owner => {
                    // Don't show creator themselves if same department
                    if (deptId == creatorDeptId && owner.id == creatorId) {
                        return; // Skip showing creator in the list
                    }
                    const selected = selectedOwnerId && owner.id == selectedOwnerId ? 'selected' : '';
                    // Prefer name, then email for display
                    const displayName = owner.name || owner.email || 'Unknown';
                    ownerSelect.innerHTML += `<option value="${owner.id}" ${selected}>${displayName}</option>`;
                });
            }
            ownerSelect.disabled = false;
        } catch (err) {
            console.error('Failed to load owners', err);
            let errorMsg = 'Failed to load owners';
            if (err.message) {
                errorMsg += ': ' + err.message;
            }
            ownerSelect.innerHTML = `<option value="">${errorMsg}</option>`;
        }
    }
    
    // Auto-fill document type based on file extension and validate file
    document.addEventListener('DOMContentLoaded', function() {
        const fileInput = document.getElementById('fileInput');
        const documentTypeInput = document.getElementById('documentTypeInput');
        const fileErrorDiv = document.getElementById('fileError');
        const createDocumentForm = document.getElementById('createDocumentForm');
        const createDocumentModal = document.getElementById('createDocumentModal');
        
        // Mapping of file extensions to document types (most common document types)
        const extensionMap = {
            'doc': 'DOC',
            'docx': 'DOCX',
            'pdf': 'PDF',
            'xls': 'XLS',
            'xlsx': 'XLSX',
            'ppt': 'PPT',
            'pptx': 'PPTX',
            'txt': 'TXT',
            'csv': 'CSV',
            'rtf': 'RTF',
            'odt': 'ODT'
        };
        
        // Supported file extensions
        const supportedExtensions = Object.keys(extensionMap);
        const maxFileSize = 50 * 1024 * 1024; // 50MB in bytes
        
        function showFileError(message) {
            if (fileErrorDiv) {
                fileErrorDiv.textContent = message;
                fileErrorDiv.classList.remove('d-none');
                fileErrorDiv.classList.add('d-block');
                if (fileInput) {
                    fileInput.classList.add('is-invalid');
                }
            }
        }
        
        function hideFileError() {
            if (fileErrorDiv) {
                fileErrorDiv.textContent = '';
                fileErrorDiv.classList.add('d-none');
                fileErrorDiv.classList.remove('d-block');
                if (fileInput) {
                    fileInput.classList.remove('is-invalid');
                }
            }
        }
        
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
        }
        
        if (fileInput && documentTypeInput) {
            fileInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                hideFileError();
                
                if (file) {
                    const fileName = file.name.toLowerCase();
                    const fileSize = file.size;
                    // Extract file extension
                    const extension = fileName.split('.').pop();
                    
                    // Validate file size
                    if (fileSize > maxFileSize) {
                        showFileError(`The file is too large (${formatFileSize(fileSize)}). Maximum file size is 50MB. Please choose a smaller file.`);
                        fileInput.value = ''; // Clear the file input
                        documentTypeInput.value = '';
                        return;
                    }
                    
                    // Validate file type
                    if (!extension || !supportedExtensions.includes(extension)) {
                        showFileError('The file type is not supported. Please upload one of the following formats: DOC, DOCX, PDF, XLS, XLSX, PPT, PPTX, TXT, CSV, RTF, or ODT.');
                        fileInput.value = ''; // Clear the file input
                        documentTypeInput.value = '';
                        return;
                    }
                    
                    // Check if we have a mapping for this extension
                    if (extensionMap[extension]) {
                        // Auto-fill the document type (field is read-only)
                        documentTypeInput.value = extensionMap[extension];
                    } else {
                        // If extension is not recognized, clear the field
                        documentTypeInput.value = '';
                    }
                } else {
                    // If no file selected, clear the field
                    documentTypeInput.value = '';
                }
            });
        }
        
        // Validate before form submission
        if (createDocumentForm) {
            createDocumentForm.addEventListener('submit', function(e) {
                const file = fileInput?.files[0];
                
                if (!file) {
                    e.preventDefault();
                    showFileError('Please select a file to upload.');
                    return false;
                }
                
                const fileName = file.name.toLowerCase();
                const fileSize = file.size;
                const extension = fileName.split('.').pop();
                
                // Validate file size
                if (fileSize > maxFileSize) {
                    e.preventDefault();
                    showFileError(`The file is too large (${formatFileSize(fileSize)}). Maximum file size is 50MB. Please choose a smaller file.`);
                    return false;
                }
                
                // Validate file type
                if (!extension || !supportedExtensions.includes(extension)) {
                    e.preventDefault();
                    showFileError('The file type is not supported. Please upload one of the following formats: DOC, DOCX, PDF, XLS, XLSX, PPT, PPTX, TXT, CSV, RTF, or ODT.');
                    return false;
                }
                
                hideFileError();
            });
        }
        
        // Clear errors and reset form when modal is closed
        if (createDocumentModal) {
            createDocumentModal.addEventListener('hidden.bs.modal', function() {
                hideFileError();
                if (fileInput) {
                    fileInput.value = '';
                    fileInput.classList.remove('is-invalid');
                }
                if (documentTypeInput) {
                    documentTypeInput.value = '';
                }
            });
        }
    });
    
    // Owner Incoming: helpers
    function setActiveNav(page) {
        document.querySelectorAll('.nav-btn').forEach(btn => {
            btn.classList.toggle('active', btn.getAttribute('data-page') === page);
        });
    }
    async function loadIncoming() {
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const res = await fetch('/api/documents/incoming', {
                headers: { 
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            });
            
            if (!res.ok) {
                throw new Error(`HTTP error! status: ${res.status}`);
            }
            
            const data = await res.json();
            console.log('Incoming API response:', data); // Debug log
            
            // Handle paginated response structure
            const items = Array.isArray(data.data) ? data.data : [];
            const total = data.total ?? (items ? items.length : 0);
            
            const tbody = document.querySelector('#incomingTable tbody');
            const countEl = document.getElementById('incomingCount');
            
            if (countEl) countEl.textContent = total;
            
            if (!items || items.length === 0) {
                if (tbody) tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4">No incoming documents</td></tr>';
                return;
            }
            
            const rows = items.map(doc => {
                // Handle both direct document objects and nested document objects
                const d = doc.document || doc;
                const id = d.id || doc.id;
                const title = (d.title || doc.title || '—').toString();
                const type = (d.document_type || doc.document_type || '—').toString();
                const desc = (d.description || doc.description || '—').toString();
                const status = (d.current_status || doc.current_status || '—').toString();
                const descDisplay = desc.length > 60 ? desc.substring(0, 60) + '…' : desc;
                
                return `<tr>
                    <td>${title}</td>
                    <td>${type}</td>
                    <td>${descDisplay}</td>
                    <td><span class="doc-status-badge ${getStatusClass(status)}">${formatStatusText(status)}</span></td>
                    <td>
                        <button class="btn btn-sm btn-outline-info" onclick="viewDocumentModal(${id})"><i class="bi bi-eye"></i></button>
                        ${status === 'pending_recipient_owner' ? `<button class="btn btn-sm btn-success" onclick="receiveAsOwner(${id})"><i class="bi bi-download"></i> Receive</button>` : ''}
                    </td>
                </tr>`;
            });
            
            if (tbody) tbody.innerHTML = rows.join('');
        } catch (e) {
            console.error('Error loading incoming documents:', e);
            const tbody = document.querySelector('#incomingTable tbody');
            if (tbody) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center text-danger py-4">Failed to load: ' + (e.message || 'Unknown error') + '</td></tr>';
            }
        }
    }
    // Load received docs
    async function loadReceived() {
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const searchEl = document.getElementById('receivedSearch');
            const documentTypeEl = document.getElementById('receivedDocumentType');
            const departmentEl = document.getElementById('receivedDepartment');
            
            const search = searchEl ? (searchEl.value || '').trim() : '';
            const documentType = documentTypeEl ? (documentTypeEl.value || '').trim() : '';
            const departmentId = departmentEl ? (departmentEl.value || '').trim() : '';
            
            const params = new URLSearchParams();
            if (search) params.append('search', search);
            if (documentType) params.append('document_type', documentType);
            if (departmentId) params.append('department_id', departmentId);
            
            const url = '/api/documents/received' + (params.toString() ? '?' + params.toString() : '');
            const res = await fetch(url, {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            });
            if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);
            const data = await res.json();
            const items = Array.isArray(data.data) ? data.data : [];
            const tbody = document.querySelector('#receivedTable tbody');
            if (!items || items.length === 0) {
                tbody.innerHTML = '<tr><td colspan="11" class="text-center py-4">No received documents</td></tr>';
                updateReceivedFiltersVisibility();
                return;
            }
            const rows = items.map(d => {
                const id = d.id;
                const title = (d.title || '—').toString();
                const type = (d.document_type || '—').toString();
                const desc = (d.description || '—').toString();
                const purpose = (d.purpose || '—').toString();
                const owner = (d.owner || '—').toString();
                const targetOwner = (d.target_owner || '—').toString();
                const department = (d.receiving_department || d.department || '—').toString();
                const createdDate = d.created_at ? (() => {
                    const date = new Date(d.created_at);
                    const year = date.getFullYear();
                    const month = String(date.getMonth() + 1).padStart(2, '0');
                    const day = String(date.getDate()).padStart(2, '0');
                    const hours = String(date.getHours()).padStart(2, '0');
                    const minutes = String(date.getMinutes()).padStart(2, '0');
                    return `${year}-${month}-${day} ${hours}:${minutes}`;
                })() : '—';
                const status = (d.current_status || '—').toString();
                const hasFile = d.has_file || false;
                const descDisplay = desc.length > 60 ? desc.substring(0, 60) + '…' : desc;
                const fileButton = hasFile || id ? `<button class="btn btn-sm btn-outline-info" onclick="viewDocumentModal(${id})" title="View"><i class="bi bi-eye"></i></button>` : '—';
                const statusClass = getStatusClass(status);
                const statusText = formatStatusText(status);
                const actionButtons = `<div class="d-flex gap-2 justify-content-center">
                    <button class="btn btn-sm btn-outline-secondary" onclick="archiveDocument(${id})" title="Archive">
                        <i class="bi bi-archive"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger" onclick="deleteDocument(${id})" title="Delete">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>`;
                return `<tr>
                    <td>${title}</td>
                    <td>${type}</td>
                    <td>${descDisplay}</td>
                    <td>${purpose}</td>
                    <td><span class="doc-status-badge doc-status-received">Received</span></td>
                    <td><strong>Sender:</strong> ${owner}</td>
                    <td>${department}</td>
                    <td>${createdDate}</td>
                    <td>${fileButton}</td>
                    <td><span class="doc-status-badge ${statusClass}">${statusText}</span></td>
                    <td>${actionButtons}</td>
                </tr>`;
            });
            tbody.innerHTML = rows.join('');
            updateReceivedFiltersVisibility();
        } catch (e) {
            console.error('Error loading received documents:', e);
            const tbody = document.querySelector('#receivedTable tbody');
            if (tbody) tbody.innerHTML = '<tr><td colspan="11" class="text-center text-danger py-4">Failed to load</td></tr>';
        }
    }
    
    // Update received filters visibility
    function updateReceivedFiltersVisibility() {
        const search = document.getElementById('receivedSearch')?.value || '';
        const documentType = document.getElementById('receivedDocumentType')?.value || '';
        const departmentId = document.getElementById('receivedDepartment')?.value || '';
        const clearBtn = document.getElementById('clearReceivedFiltersBtn');
        
        if (clearBtn) {
            if (search || documentType || departmentId) {
                clearBtn.style.display = 'inline-block';
            } else {
                clearBtn.style.display = 'none';
            }
        }
    }
    
    // Clear received filters
    function clearReceivedFilters() {
        const search = document.getElementById('receivedSearch');
        const documentType = document.getElementById('receivedDocumentType');
        const departmentId = document.getElementById('receivedDepartment');
        
        if (search) search.value = '';
        if (documentType) documentType.value = '';
        if (departmentId) departmentId.value = '';
        
        loadReceived();
    }
    
    // Load archived docs
    async function loadArchived() {
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const res = await fetch('/api/documents/archived', {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            });
            if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);
            const data = await res.json();
            const items = Array.isArray(data.data) ? data.data : [];
            const tbody = document.querySelector('#archivedTable tbody');
            if (!items || items.length === 0) {
                tbody.innerHTML = '<tr><td colspan="11" class="text-center py-4">No archived documents</td></tr>';
                return;
            }
            const rows = items.map(d => {
                const id = d.id;
                const title = (d.title || '—').toString();
                const type = (d.document_type || '—').toString();
                const desc = (d.description || '—').toString();
                const purpose = (d.purpose || '—').toString();
                const owner = (d.owner || '—').toString();
                const targetOwner = (d.target_owner || '—').toString();
                const department = (d.receiving_department || d.department || '—').toString();
                const createdDate = d.created_at ? (() => {
                    const date = new Date(d.created_at);
                    const year = date.getFullYear();
                    const month = String(date.getMonth() + 1).padStart(2, '0');
                    const day = String(date.getDate()).padStart(2, '0');
                    const hours = String(date.getHours()).padStart(2, '0');
                    const minutes = String(date.getMinutes()).padStart(2, '0');
                    return `${year}-${month}-${day} ${hours}:${minutes}`;
                })() : '—';
                const status = (d.current_status || 'Archived').toString();
                const hasFile = d.has_file || false;
                const descDisplay = desc.length > 60 ? desc.substring(0, 60) + '…' : desc;
                const fileButton = hasFile || id ? `<button class="btn btn-sm btn-outline-info" onclick="viewDocumentModal(${id})" title="View"><i class="bi bi-eye"></i></button>` : '—';
                const statusClass = getStatusClass(status);
                const statusText = formatStatusText(status);
                const actionButtons = `<div class="d-flex gap-2 justify-content-center">
                    <button class="btn btn-sm btn-outline-success" onclick="unarchiveDocument(${id})" title="Unarchive">
                        <i class="bi bi-archive"></i> Unarchive
                    </button>
                    <button class="btn btn-sm btn-outline-danger" onclick="deleteDocument(${id})" title="Delete">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>`;
                return `<tr>
                    <td>${title}</td>
                    <td>${type}</td>
                    <td>${descDisplay}</td>
                    <td>${purpose}</td>
                    <td><span class="doc-status-badge doc-status-received">Received</span></td>
                    <td><strong>Sender:</strong> ${owner}</td>
                    <td>${department}</td>
                    <td>${createdDate}</td>
                    <td>${fileButton}</td>
                    <td><span class="doc-status-badge ${statusClass}">${statusText}</span></td>
                    <td>${actionButtons}</td>
                </tr>`;
            });
            tbody.innerHTML = rows.join('');
        } catch (e) {
            console.error('Error loading archived documents:', e);
            const tbody = document.querySelector('#archivedTable tbody');
            if (tbody) tbody.innerHTML = '<tr><td colspan="11" class="text-center text-danger py-4">Failed to load</td></tr>';
        }
    }
    // Show confirmation modal
    let currentConfirmHandler = null;
    function showConfirmationModal(title, message, confirmCallback, confirmText = 'Confirm', confirmClass = 'btn-primary') {
        const modalEl = document.getElementById('confirmationModal');
        const modal = new bootstrap.Modal(modalEl);
        
        document.getElementById('confirmationModalTitle').innerHTML = `<i class="bi bi-exclamation-triangle me-2"></i>${title}`;
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
        
        // Show modal
        modal.show();
    }

    // Archive
    async function archiveDocument(docId) {
        showConfirmationModal(
            'Archive Document',
            'Are you sure you want to archive this document?',
            async function() {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                const res = await fetch(`/api/documents/${docId}/archive`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({})
                });
                const data = await res.json().catch(() => ({}));
                if (res.ok) {
                    if (typeof showToast === 'function') showToast('Document archived');
                    loadReceived();
                    loadArchived();
                } else {
                    if (typeof showToast === 'function') showToast(data.message || 'Failed to archive', 'error');
                }
            },
            'Archive',
            'btn-warning'
        );
    }
    // Unarchive
    async function unarchiveDocument(docId) {
        showConfirmationModal(
            'Unarchive Document',
            'Are you sure you want to unarchive this document?',
            async function() {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                const res = await fetch(`/api/documents/${docId}/unarchive`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({})
                });
                const data = await res.json().catch(() => ({}));
                if (res.ok) {
                    if (typeof showToast === 'function') showToast('Document unarchived successfully');
                    loadReceived();
                    loadArchived();
                } else {
                    if (typeof showToast === 'function') showToast(data.message || 'Failed to unarchive', 'error');
                }
            },
            'Unarchive',
            'btn-success'
        );
    }
    // Delete
    async function deleteDocument(docId) {
        showConfirmationModal(
            'Delete Document',
            'Are you sure you want to delete this document? This action cannot be undone.',
            async function() {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                const res = await fetch(`/documents/${docId}`, {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin'
                });
                const data = await res.json().catch(() => ({}));
                if (res.ok) {
                    if (typeof showToast === 'function') showToast('Document deleted');
                    loadReceived();
                    loadArchived();
                } else {
                    if (typeof showToast === 'function') showToast(data.message || 'Failed to delete', 'error');
                }
            },
            'Delete',
            'btn-danger'
        );
    }
    async function receiveAsOwner(docId) {
        showConfirmationModal(
            'Receive Document',
            'Are you sure you want to receive this document?',
            async function() {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                const res = await fetch(`/api/owner/documents/${docId}/receive`, {
                    method: 'POST',
                    headers: { 
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Content-Type': 'application/json'
                    },
                    credentials: 'same-origin'
                });
                if (res.ok) {
                    if (typeof showToast === 'function') showToast('Document received');
                    loadIncoming();
                } else {
                    const data = await res.json().catch(() => ({}));
                    if (typeof showToast === 'function') showToast(data.message || 'Failed to receive', 'error');
                }
            },
            'Receive',
            'btn-success'
        );
    }
    function navigateTo(page) {
        setActiveNav(page);
        const dashboardSection = document.getElementById('recent-docs');
        const incomingSection = document.getElementById('incoming-list');
        const receivedSection = document.getElementById('received-list');
        const statsSection = document.getElementById('stats');
        
        if (page === 'incoming') {
            if (dashboardSection) dashboardSection.style.display = 'none';
            if (statsSection) statsSection.style.display = 'none';
            if (incomingSection) {
                incomingSection.style.display = 'block';
                loadIncoming();
            }
            if (receivedSection) receivedSection.style.display = 'none';
        } else if (page === 'received') {
            if (dashboardSection) dashboardSection.style.display = 'none';
            if (statsSection) statsSection.style.display = 'none';
            if (incomingSection) incomingSection.style.display = 'none';
            if (receivedSection) {
                receivedSection.style.display = 'block';
                // Attach filter event listeners when section is shown
                attachReceivedFilters();
                // Only load received by default, archived will load when tab is clicked
                loadReceived();
            }
        } else {
            if (incomingSection) incomingSection.style.display = 'none';
            if (receivedSection) receivedSection.style.display = 'none';
            if (statsSection) statsSection.style.display = 'grid';
            if (dashboardSection) dashboardSection.style.display = 'block';
        }
    }

    // Attach received filters event listeners
    function attachReceivedFilters() {
        const receivedSearch = document.getElementById('receivedSearch');
        const receivedDocumentType = document.getElementById('receivedDocumentType');
        const receivedDepartment = document.getElementById('receivedDepartment');
        
        // Remove existing listeners to avoid duplicates
        if (receivedSearch && !receivedSearch.hasAttribute('data-listener-attached')) {
            let searchTimeout;
            receivedSearch.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    loadReceived();
                }, 500);
            });
            receivedSearch.setAttribute('data-listener-attached', 'true');
        }
        
        if (receivedDocumentType && !receivedDocumentType.hasAttribute('data-listener-attached')) {
            receivedDocumentType.addEventListener('change', function() {
                loadReceived();
            });
            receivedDocumentType.setAttribute('data-listener-attached', 'true');
        }
        
        if (receivedDepartment && !receivedDepartment.hasAttribute('data-listener-attached')) {
            receivedDepartment.addEventListener('change', function() {
                loadReceived();
            });
            receivedDepartment.setAttribute('data-listener-attached', 'true');
        }
    }
    
    // Keep modal open on validation errors and restore selected values
    document.addEventListener('DOMContentLoaded', function() {
        // Load incoming count on page load
        loadIncomingCount();
        
        // Try to attach received filters (they might be hidden)
        attachReceivedFilters();
        
        const modal = document.getElementById('createDocumentModal');
        const hasErrors = {{ $errors->any() ? 'true' : 'false' }};
        const oldReceivingDeptId = {{ old('receiving_department_id') ?: 'null' }};
        const oldTargetOwnerId = {{ old('target_owner_id') ?: 'null' }};
        
        // If there are errors, show the modal and restore selections
        if (hasErrors && modal) {
            const bsModal = new bootstrap.Modal(modal);
            bsModal.show();
            
            // Restore receiving department selection
            if (oldReceivingDeptId) {
                const deptSelect = document.getElementById('receivingDepartmentSelect');
                if (deptSelect) {
                    deptSelect.value = oldReceivingDeptId;
                    // Load owners for the selected department
                    loadTargetOwners(oldReceivingDeptId, oldTargetOwnerId);
                }
            }
        }
    });
    
    // Load just the count for the dashboard
    async function loadIncomingCount() {
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const res = await fetch('/api/documents/incoming', {
                headers: { 
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            });
            if (res.ok) {
                const data = await res.json();
                const total = data.total ?? 0;
                const countEl = document.getElementById('incomingCount');
                if (countEl) countEl.textContent = total;
            }
        } catch (e) {
            console.error('Error loading incoming count:', e);
        }
    }
    
    // Document View Modal for Owner
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
            const statusClass = getStatusClass(doc.current_status || '');
            const statusText = formatStatusText(doc.current_status || '—');
            const rejectionReasonHtml = doc.rejection_reason ? `<div class="mt-2 p-2 bg-danger bg-opacity-10 border border-danger rounded"><strong><i class="bi bi-exclamation-triangle"></i> Rejection Reason:</strong><br>${doc.rejection_reason}</div>` : '';
            // Generate QR code for document - always show container, generate QR code if libraries available
            const qrCodeHtml = `<div class="card border-primary mb-3"><div class="card-body text-center"><h6 class="card-title"><i class="bi bi-qr-code"></i> Document QR Code</h6><div id="qr-container-${docId}" style="min-height: 200px; display: flex; align-items: center; justify-content: center;"></div><p class="text-muted small mt-2">Scan this QR code to quickly access document details</p></div></div>`;
            
            contentDiv.innerHTML = `${qrCodeHtml}<div class="mb-4"><h4>${doc.title || 'Document'}</h4><p class="text-muted"><i class="bi bi-file-earmark"></i> Document Code: #${doc.id}</p></div><div class="row"><div class="col-md-6 mb-3"><strong>Document Type:</strong><br><span>${doc.document_type || '—'}</span></div><div class="col-md-6 mb-3"><strong>Status:</strong><br><span class="doc-status-badge ${statusClass}">${statusText}</span>${rejectionReasonHtml}</div><div class="col-md-6 mb-3"><strong>Purpose:</strong><br><span>${doc.purpose || '—'}</span></div><div class="col-md-6 mb-3"><strong>Created Date:</strong><br><span>${doc.created_at || '—'}</span></div><div class="col-md-6 mb-3"><strong>Department:</strong><br><span>${doc.department || '—'}</span></div><div class="col-md-6 mb-3"><strong>Receiving Department:</strong><br><span>${doc.receiving_department || '—'}</span></div></div><div class="mb-3"><strong>Description:</strong><div class="mt-2 p-3 bg-light border-start border-primary border-4 rounded">${doc.description || 'No description provided.'}</div></div>${doc.has_file ? `<div class="p-4 bg-info bg-opacity-10 border border-info rounded mt-4"><h5 class="mb-3"><i class="bi bi-file-earmark-text"></i> File Information</h5><div class="row"><div class="col-md-6 mb-3"><strong>File Name:</strong><br><span>${doc.file_name || '—'}</span></div><div class="col-md-6 mb-3"><strong>File Type:</strong><br><span>${doc.file_mime || '—'}</span></div>${doc.file_size ? `<div class="col-md-6 mb-3"><strong>File Size:</strong><br><span>${fileSize}</span></div>` : ''}</div><div class="text-center mt-3"><p class="text-muted mb-0">Download the file to your device to view its contents</p></div></div>` : '<div class="alert alert-warning mt-4"><i class="bi bi-exclamation-triangle"></i> No file attached.</div>'}`;
            
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

    // QR Code Scanning Modal
    function initQRScanner() {
        // Ensure libraries are loaded
        if (typeof Html5Qrcode === 'undefined') {
            console.error('QR Scanner library not loaded');
            return;
        }
    }

    // Handle QR code scan
    async function handleQRScan(decodedText) {
        if (!window.QRCodeHandler) {
            console.error('QRCodeHandler not available');
            return;
        }

        const documentId = window.QRCodeHandler.extractDocumentId(decodedText);
        if (!documentId) {
            if (typeof showToast === 'function') {
                showToast('Invalid QR code. Please scan a valid document QR code.', 'error');
            } else {
                alert('Invalid QR code. Please scan a valid document QR code.');
            }
            return;
        }

        // Stop scanning
        await window.QRCodeHandler.stopScanning();

        // Close scan modal
        const scanModal = bootstrap.Modal.getInstance(document.getElementById('scanQRModal'));
        if (scanModal) {
            scanModal.hide();
        }

        // Open document details
        if (typeof viewDocumentModal === 'function') {
            viewDocumentModal(documentId);
        } else {
            // Fallback: redirect to document view page
            window.location.href = `/documents/${documentId}/view`;
        }
    }

    // Initialize scanner when modal is shown
    document.addEventListener('DOMContentLoaded', function() {
        const scanModal = document.getElementById('scanQRModal');
        if (scanModal) {
            scanModal.addEventListener('show.bs.modal', async function() {
                const scanElement = document.getElementById('qr-reader');
                if (scanElement && window.QRCodeHandler) {
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
                                } else if (typeof showToast === 'function') {
                                    showToast(error, 'error');
                                } else {
                                    console.error('QR Scan error:', error);
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

    <!-- Confirmation Modal -->
    <div class="modal fade" id="confirmationModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmationModalTitle">
                        <i class="bi bi-exclamation-triangle me-2"></i>Confirm Action
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p id="confirmationModalMessage" class="mb-0">Are you sure you want to proceed?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirmationModalConfirm">Confirm</button>
                </div>
            </div>
        </div>
    </div>
</body>
</html>


