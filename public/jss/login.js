// login.js - Password Toggle Functionality

function togglePassword() {
    const passwordInput = document.getElementById('password');
    const eyeIcon = document.getElementById('eye-icon');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        // Eye-off icon (password visible)
        eyeIcon.innerHTML = '<path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/><path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/><line x1="2" x2="22" y1="2" y2="22"/>';
    } else {
        passwordInput.type = 'password';
        // Eye icon (password hidden)
        eyeIcon.innerHTML = '<path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/>';
    }
}

// Function to accept terms from modal
function acceptTermsFromModal() {
    const acceptBtn = document.getElementById('acceptTermsBtn');
    // Don't proceed if button is disabled
    if (acceptBtn && acceptBtn.disabled) {
        return;
    }
    
    const termsCheckbox = document.getElementById('acceptTerms');
    if (termsCheckbox) {
        termsCheckbox.checked = true;
        // Store acceptance in localStorage
        try {
            localStorage.setItem('dts_terms_accepted', 'true');
        } catch (e) {
            console.error('Failed to save terms acceptance:', e);
        }
    }
    
    // Close the modal
    const termsModal = document.getElementById('termsModal');
    if (termsModal) {
        const Bootstrap = window.bootstrap || bootstrap;
        if (Bootstrap && Bootstrap.Modal) {
            let modal = Bootstrap.Modal.getInstance(termsModal);
            if (!modal) {
                modal = new Bootstrap.Modal(termsModal);
            }
            modal.hide();
        }
    }
}

// Check if user has previously accepted terms
function checkPreviousTermsAcceptance() {
    try {
        const accepted = localStorage.getItem('dts_terms_accepted');
        if (accepted === 'true') {
            const termsCheckbox = document.getElementById('acceptTerms');
            if (termsCheckbox) {
                termsCheckbox.checked = true;
            }
        }
    } catch (e) {
        console.error('Failed to check terms acceptance:', e);
    }
}

// Handle terms modal scroll detection
function setupTermsModalScroll() {
    const termsModal = document.getElementById('termsModal');
    const termsContent = document.querySelector('.terms-content');
    const acceptBtn = document.getElementById('acceptTermsBtn');
    
    if (!termsModal || !termsContent || !acceptBtn) return;
    
    // Reset button state when modal is shown
    termsModal.addEventListener('show.bs.modal', function() {
        acceptBtn.disabled = true;
        // Reset scroll position
        termsContent.scrollTop = 0;
    });
    
    // Check scroll position
    termsContent.addEventListener('scroll', function() {
        const scrollTop = termsContent.scrollTop;
        const scrollHeight = termsContent.scrollHeight;
        const clientHeight = termsContent.clientHeight;
        
        // Enable button when scrolled to bottom (with 10px tolerance)
        if (scrollTop + clientHeight >= scrollHeight - 10) {
            acceptBtn.disabled = false;
        } else {
            acceptBtn.disabled = true;
        }
    });
    
    // Also check on initial load in case content is already fully visible
    setTimeout(function() {
        const scrollTop = termsContent.scrollTop;
        const scrollHeight = termsContent.scrollHeight;
        const clientHeight = termsContent.clientHeight;
        
        if (scrollTop + clientHeight >= scrollHeight - 10) {
            acceptBtn.disabled = false;
        }
    }, 100);
}

// Intercept login form submission to use API login
document.addEventListener('DOMContentLoaded', function () {
    // Check if user has previously accepted terms
    checkPreviousTermsAcceptance();
    
    // Setup terms modal scroll detection
    setupTermsModalScroll();
    
    const form = document.querySelector('.login-form');
    if (!form) return;

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        
        // Check if terms are accepted
        const termsCheckbox = document.getElementById('acceptTerms');
        if (!termsCheckbox || !termsCheckbox.checked) {
            showLoginErrorModal('Please accept the Terms of Service and End-User Agreement to continue.');
            return;
        }
        
        const email = document.getElementById('email')?.value || '';
        const password = document.getElementById('password')?.value || '';
        
        // Get CSRF token from meta tag or form input
        let csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || 
                       document.querySelector('input[name="_token"]')?.value || '';

        hideFieldPopover('password');

        try {
            const response = await fetch('/api/login', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin', // Include cookies for session
                body: JSON.stringify({ email, password, device_name: 'web' })
            });

            if (!response.ok) {
                let errorMessage = 'Login failed';
                try {
                    const err = await response.json();
                    errorMessage = err.message || err.errors?.email?.[0] || errorMessage;
                } catch (e) {
                    // If response is not JSON, use status text
                    errorMessage = response.status === 401 ? 'Invalid email or password' : 
                                  response.status === 403 ? 'Account deactivated. Please contact an administrator.' :
                                  response.status === 422 ? 'Invalid credentials' : 
                                  'Login failed. Please try again.';
                }
                hideFieldPopover('password');
                showLoginErrorModal(errorMessage);
                return;
            }

            const data = await response.json();
            // Optionally store token if you plan to call other API endpoints from JS
            try { localStorage.setItem('auth_token', data.token); } catch (_) {}
            
            // Store terms acceptance when login is successful
            const termsCheckbox = document.getElementById('acceptTerms');
            if (termsCheckbox && termsCheckbox.checked) {
                try {
                    localStorage.setItem('dts_terms_accepted', 'true');
                } catch (_) {}
            }

            // Web session has been created on the server; redirect to dashboard
            window.location.href = '/dashboard';
        } catch (error) {
            showLoginErrorModal('Network error. Please try again.');
        }
    });
    // Hide popover when user edits the password
    const passwordInput = document.getElementById('password');
    if (passwordInput) {
        passwordInput.addEventListener('input', function () { hideFieldPopover('password'); });
        passwordInput.addEventListener('focus', function () { hideFieldPopover('password'); });
    }
});

function showLoginErrorModal(message) {
    const msgEl = document.getElementById('loginErrorMessage');
    if (msgEl) {
        msgEl.textContent = message;
    }
    const modalEl = document.getElementById('loginErrorModal');
    if (!modalEl) return;
    const modal = new (window.bootstrap?.Modal || bootstrap.Modal)(modalEl);
    modal.show();
}

function showFieldPopover(fieldId, message) {
    const el = document.getElementById(fieldId);
    if (!el) return;
    // Dispose existing popover if present
    if (el._errorPopover && typeof el._errorPopover.dispose === 'function') {
        el._errorPopover.dispose();
    }
    const Popover = window.bootstrap?.Popover || bootstrap.Popover;
    el._errorPopover = new Popover(el, {
        content: message || 'Invalid value',
        placement: 'bottom',
        trigger: 'manual',
        container: 'body',
        html: false,
        customClass: 'field-error-popover'
    });
    el._errorPopover.show();
}

function hideFieldPopover(fieldId) {
    const el = document.getElementById(fieldId);
    if (el && el._errorPopover && typeof el._errorPopover.hide === 'function') {
        el._errorPopover.hide();
    }
}