// dashboard.js - Dashboard Functionality

// Navigation handler
function navigateTo(page, event) {
    // Prevent any default behavior if event is provided
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    
    console.log('navigateTo called with page:', page, 'current path:', window.location.pathname);
    
    if (!page) {
        console.error('navigateTo: page parameter is missing');
        return;
    }
    
    const onDashboard = window.location.pathname.includes('/dashboard');

    const scrollToId = (id) => {
        const el = document.getElementById(id);
        if (el) {
            // update hash for reliability, then smooth-scroll
            if (window.location.hash !== `#${id}`) {
                history.replaceState(null, '', `#${id}`);
            }
            el.scrollIntoView({ behavior: 'smooth', block: 'start' });
        } else {
            window.location.href = `/dashboard#${id}`;
        }
    };

    switch (page) {
        case 'dashboard':
            window.location.href = '/dashboard';
            break;
        case 'repository':
            if (onDashboard) { scrollToId('recent-docs'); return; }
            window.location.href = '/dashboard#recent-docs';
            break;
        case 'my-docs':
            window.location.href = '/my-documents';
            break;
        case 'tracking':
            if (onDashboard) return scrollToId('stats');
            window.location.href = '/dashboard#stats';
            break;
        case 'incoming':
            window.location.href = '/handler/incoming';
            break;
        case 'to-process':
            window.location.href = '/handler/to-process';
            break;
        case 'on-hold':
            window.location.href = '/handler/on-hold';
            break;
        case 'outgoing':
            window.location.href = '/handler/outgoing';
            break;
        case 'file-handling':
        case 'hold':
            if (onDashboard) { scrollToId('pending-routes'); return; }
            window.location.href = '/dashboard#pending-routes';
            break;
        case 'activity-logs':
            window.location.href = '/admin/activity-logs';
            break;
        case 'logs':
        case 'audit-logs':
            window.location.href = '/audit-logs';
            break;
        case 'released':
            window.location.href = '/owner/released';
            break;
        case 'for-review':
            window.location.href = '/owner/for-review';
            break;
        case 'complete':
            window.location.href = '/owner/complete';
            break;
        case 'archived':
            window.location.href = '/owner/archived';
            break;
        case 'deleted':
            window.location.href = '/owner/deleted';
            break;
        case 'users':
            window.location.href = '/admin/users';
            break;
        case 'departments':
            window.location.href = '/admin/departments';
            break;
        case 'bug-reports':
            const bugReportsPath = '/admin/bug-reports';
            const currentPath = window.location.pathname;
            // If already on bug reports page, do nothing
            if (currentPath === bugReportsPath || currentPath.startsWith(bugReportsPath + '/')) {
                console.log('Already on bug reports page');
                return; // Already on bug reports page, don't reload
            }
            // Navigate to bug reports page
            console.log('Navigating to bug reports:', bugReportsPath);
            window.location.href = bugReportsPath;
            return;
        default:
            window.location.href = '/dashboard';
    }
}

// Simple toast
function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.textContent = message;
    toast.style.position = 'fixed';
    toast.style.right = '16px';
    toast.style.bottom = '16px';
    toast.style.padding = '10px 14px';
    toast.style.borderRadius = '6px';
    toast.style.color = '#fff';
    toast.style.zIndex = 9999;
    toast.style.background = type === 'error' ? '#dc2626' : '#16a34a';
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 2500);
}

async function postJson(url = '', data = {}) {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const res = await fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': token || ''
        },
        body: JSON.stringify(data)
    });
    if (!res.ok) {
        const text = await res.text();
        throw new Error(text || 'Request failed');
    }
    return res.json().catch(() => ({}));
}

async function createNewDocument() {
    const title = prompt('Enter document title');
    if (!title) return;
    try {
        await postJson('/documents/register', { title });
        showToast('Document registered');
    } catch (e) {
        console.error(e);
        showToast('Failed to register document', 'error');
    }
}

async function sendDocument(documentId, toDepartmentId, toUserId = null) {
    try {
        await postJson(`/documents/${documentId}/send`, { to_department_id: toDepartmentId, to_user_id: toUserId });
        showToast('Document sent');
    } catch (e) {
        console.error(e);
        showToast('Failed to send document', 'error');
    }
}

async function receiveRoute(routeId) {
    try {
        await postJson(`/routes/${routeId}/receive`, {});
        showToast('Document received');
        location.reload();
    } catch (e) {
        console.error(e);
        showToast('Failed to receive document', 'error');
    }
}

async function forwardDocumentToOwner(documentId) {
    try {
        await postJson(`/documents/${documentId}/forward-to-owner`, {});
        showToast('Forwarded to owner');
    } catch (e) {
        console.error(e);
        showToast('Failed to forward', 'error');
    }
}

function viewDocumentsByStatus(status) {
    if (status === 'received') navigateTo('tracking');
}

// Mobile Menu Toggle Functionality
function initMobileMenu() {
    const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
    const sidebar = document.querySelector('.sidebar');
    const sidebarOverlay = document.querySelector('.sidebar-overlay');
    
    if (!mobileMenuToggle || !sidebar) return;
    
    function openSidebar() {
        sidebar.classList.add('active');
        if (sidebarOverlay) {
            sidebarOverlay.classList.add('active');
        }
        document.body.style.overflow = 'hidden'; // Prevent body scroll when sidebar is open
    }
    
    function closeSidebar() {
        sidebar.classList.remove('active');
        if (sidebarOverlay) {
            sidebarOverlay.classList.remove('active');
        }
        document.body.style.overflow = ''; // Restore body scroll
    }
    
    // Toggle sidebar on button click
    mobileMenuToggle.addEventListener('click', function(e) {
        e.stopPropagation();
        if (sidebar.classList.contains('active')) {
            closeSidebar();
        } else {
            openSidebar();
        }
    });
    
    // Close sidebar when clicking overlay
    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', closeSidebar);
    }
    
    // Close sidebar when clicking a nav button (on mobile)
    document.querySelectorAll('.nav-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            if (window.innerWidth <= 768) {
                setTimeout(closeSidebar, 300); // Small delay for better UX
            }
        });
    });
    
    // Close sidebar on window resize if it becomes desktop size
    let resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            if (window.innerWidth > 768) {
                closeSidebar();
            }
        }, 250);
    });
    
    // Close sidebar on ESC key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && sidebar.classList.contains('active')) {
            closeSidebar();
        }
    });
}

document.addEventListener('DOMContentLoaded', function() {
    // Initialize mobile menu
    initMobileMenu();
    
    const currentPath = window.location.pathname;
    document.querySelectorAll('.nav-btn').forEach(btn => {
        if (btn.dataset.page) {
            const pageValue = btn.dataset.page;
            let isActive = false;
            
            // More precise matching: check exact path matches first
            if (pageValue === 'dashboard' && (currentPath === '/dashboard' || currentPath === '/')) {
                isActive = true;
            } else if (pageValue === 'activity-logs' && currentPath === '/admin/activity-logs') {
                isActive = true;
            } else if ((pageValue === 'logs' || pageValue === 'audit-logs') && currentPath === '/audit-logs') {
                isActive = true;
            } else if (pageValue === 'bug-reports' && currentPath === '/admin/bug-reports') {
                isActive = true;
            } else if (pageValue === 'users' && currentPath === '/admin/users') {
                isActive = true;
            } else if (pageValue === 'departments' && currentPath === '/admin/departments') {
                isActive = true;
            } else {
                // Fallback: check if any path segment exactly matches
                const pathSegments = currentPath.split('/').filter(segment => segment.length > 0);
                isActive = pathSegments.some(segment => segment === pageValue);
            }
            
            if (isActive) {
                btn.classList.add('active');
            }
        }
    });
    if (window.location.hash) {
        const id = window.location.hash.replace('#','');
        const el = document.getElementById(id);
        if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
});

function viewDocumentDetails(documentCode) {
    console.log('Viewing document:', documentCode);
}

// Reusable Confirmation Modal
let currentConfirmHandler = null;
let confirmationModalInstance = null;

function showConfirmationModal(title, message, confirmCallback, options = {}) {
    const {
        confirmText = 'Confirm',
        confirmClass = 'btn-primary',
        cancelText = 'Cancel',
        icon = 'bi-exclamation-triangle',
        headerClass = 'bg-primary text-white',
        showCancel = true
    } = options;

    // Get or create modal element
    let modalEl = document.getElementById('globalConfirmationModal');
    if (!modalEl) {
        const modalHtml = `
            <div class="modal fade" id="globalConfirmationModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header ${headerClass}">
                            <h5 class="modal-title" id="globalConfirmationModalTitle">
                                <i class="bi ${icon} me-2"></i>${title}
                            </h5>
                            <button type="button" class="btn-close ${headerClass.includes('text-white') ? 'btn-close-white' : ''}" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body" id="globalConfirmationModalMessage">
                            ${message}
                        </div>
                        <div class="modal-footer">
                            ${showCancel ? `<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">${cancelText}</button>` : ''}
                            <button type="button" class="btn ${confirmClass}" id="globalConfirmationModalConfirm">${confirmText}</button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', modalHtml);
        modalEl = document.getElementById('globalConfirmationModal');
    }

    // Update modal content
    document.getElementById('globalConfirmationModalTitle').innerHTML = `<i class="bi ${icon} me-2"></i>${title}`;
    document.getElementById('globalConfirmationModalMessage').innerHTML = message;
    
    const confirmBtn = document.getElementById('globalConfirmationModalConfirm');
    confirmBtn.textContent = confirmText;
    confirmBtn.className = `btn ${confirmClass}`;
    
    // Remove previous event listener if exists
    if (currentConfirmHandler) {
        confirmBtn.removeEventListener('click', currentConfirmHandler);
    }
    
    // Create new handler
    currentConfirmHandler = function() {
        if (confirmationModalInstance) {
            confirmationModalInstance.hide();
        }
        if (confirmCallback) {
            confirmCallback();
        }
    };
    
    // Add new listener
    confirmBtn.addEventListener('click', currentConfirmHandler);
    
    // Create or get modal instance
    if (!confirmationModalInstance) {
        confirmationModalInstance = new bootstrap.Modal(modalEl);
    }
    
    // Show modal
    confirmationModalInstance.show();
}

// Promise-based confirmation modal (replaces confirm())
function confirmModal(title, message, options = {}) {
    return new Promise((resolve) => {
        showConfirmationModal(
            title,
            message,
            () => resolve(true),
            {
                ...options,
                cancelText: options.cancelText || 'Cancel',
                confirmText: options.confirmText || 'Confirm'
            }
        );
        
        // Handle cancel/close
        const modalEl = document.getElementById('globalConfirmationModal');
        if (modalEl) {
            const handleCancel = () => {
                modalEl.removeEventListener('hidden.bs.modal', handleCancel);
                resolve(false);
            };
            modalEl.addEventListener('hidden.bs.modal', handleCancel);
        }
    });
}