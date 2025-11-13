<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login - Document Tracking System</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body class="bg-light d-flex align-items-center min-vh-100">
    <div class="container">
        <!-- Document Tracking System Title -->
        <div class="system-title">
            <div class="d-flex align-items-center justify-content-center gap-3 mb-2">
                <img src="{{ asset('images/logo.png') }}" alt="DTS Logo">
                <h1 class="mb-0">Document Tracking System</h1>
            </div>
        </div>
        
        @if(session('qr_scan'))
            <div class="alert alert-info alert-dismissible fade show" role="alert" style="max-width: 500px; margin: 0 auto 1rem;">
                <i class="bi bi-info-circle me-2"></i>{{ session('qr_scan')['message'] }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        
        <div class="login-container">
            <!-- Header -->
            <div class="login-header">
                <h2>Login to your account</h2>
            </div>
            
            <!-- Form Body -->
            <div class="login-body">
                <form method="POST" action="{{ route('login.post') }}" class="login-form">
                    @csrf
                    
                    <!-- Email Field -->
                    <div class="mb-3">
                        <label for="email" class="form-label">EMAIL</label>
                        <input type="email" id="email" name="email" class="form-control" value="{{ old('email') }}" required>
                    </div>
                    
                    <!-- Password Field -->
                    <div class="mb-3">
                        <label for="password" class="form-label">PASSWORD</label>
                        <div class="position-relative">
                            <input type="password" id="password" name="password" class="form-control" required>
                            <button type="button" class="password-toggle-btn" onclick="togglePassword()">
                                <svg id="eye-icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/>
                                    <circle cx="12" cy="12" r="3"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Terms and Conditions Checkbox -->
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="acceptTerms" required>
                            <label class="form-check-label" for="acceptTerms">
                                I agree to the <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal" class="terms-link">Terms of Service and End-User Agreement</a>
                            </label>
                        </div>
                    </div>
                    
                    <!-- Login Button -->
                    <div class="d-flex justify-content-end pt-3">
                        <button type="submit" class="btn btn-login" id="loginBtn">Login</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script src="{{ asset('jss/login.js') }}"></script>

    <!-- Terms and Conditions Modal -->
    <div class="modal fade" id="termsModal" tabindex="-1" aria-labelledby="termsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="termsModalLabel">Terms of Service and End-User Agreement</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body terms-content">
                    <p><strong>This Terms of Service and End-User Agreement ("Agreement") is a legal agreement between you ("User" or "You") and the Document Tracking System (DTS) Development Team, Don Mariano Marcos Memorial State University (DMMMSU) Mid-La Union Campus ("We," "Us," or "Our"), regarding your use of the Document Tracking System (the "System" or "DTS").</strong></p>

                    <p class="text-danger"><strong>PLEASE READ THIS AGREEMENT CAREFULLY BEFORE USING THE SYSTEM.</strong></p>

                    <p><strong>BY REGISTERING FOR, ACCESSING, OR USING THE SYSTEM, YOU ACKNOWLEDGE THAT YOU HAVE READ, UNDERSTOOD, AND AGREE TO BE BOUND BY THE TERMS AND CONDITIONS OF THIS AGREEMENT.</strong></p>

                    <p><strong>IF YOU DO NOT AGREE TO THESE TERMS, YOU MAY NOT ACCESS OR USE THE SYSTEM.</strong></p>

                    <h6>1. Acceptance of Terms</h6>
                    <p>By creating an account, logging in, or using the DTS in any manner, you agree to be bound by this Agreement and our Privacy Policy, which is incorporated herein by reference.</p>

                    <h6>2. Definitions</h6>
                    <p><strong>Document Tracking System (DTS):</strong> Refers to the web-based platform designed for managing, tracking, and monitoring documents and their movement across various departments within an organization or institution.</p>
                    <p><strong>User:</strong> Refers to any individual authorized to use the System, including but not limited to administrators, clerks, office staff, and department heads.</p>
                    <p><strong>Document Data:</strong> Refers to any information, including metadata, attachments, or records, provided or generated through the System that pertains to official documents or transactions.</p>
                    <p><strong>Content:</strong> Refers to all information, text, files, data entries, status updates, logs, and related materials available through the System.</p>

                    <h6>3. User Accounts and Eligibility</h6>
                    <p><strong>3.1 Eligibility:</strong></p>
                    <p>Access to the DTS is restricted to authorized personnel designated by their organization or office administrator. You must have explicit authorization to create or use an account.</p>
                    <p><strong>3.2 Account Responsibility:</strong></p>
                    <p>You are responsible for maintaining the confidentiality of your login credentials (username and password). You agree to accept responsibility for all activities that occur under your account.</p>
                    <p><strong>3.3 Accurate Information:</strong></p>
                    <p>You agree to provide accurate, current, and complete information during the registration process and to promptly update such information as needed.</p>

                    <h6>4. Acceptable Use</h6>
                    <p>You agree to use the DTS solely for legitimate document tracking and administrative purposes. You expressly agree not to:</p>
                    <ul>
                        <li>Use the System for any illegal, fraudulent, or unauthorized purpose.</li>
                        <li>Share your account credentials with any unauthorized person.</li>
                        <li>Upload or transmit any content that is unlawful, harmful, or infringes on intellectual property or privacy rights.</li>
                        <li>Attempt to probe, scan, or exploit vulnerabilities of the System or its network.</li>
                        <li>Use automated systems, scripts, or bots to access or interfere with the System.</li>
                        <li>Disrupt or attempt to disrupt the integrity, performance, or security of the System.</li>
                    </ul>

                    <h6>5. Data Privacy and Confidentiality</h6>
                    <p><strong>5.1 Commitment to Privacy:</strong></p>
                    <p>We are committed to protecting the privacy and security of all User and Document Data in compliance with the Philippine Data Privacy Act of 2012 (Republic Act No. 10173).</p>
                    <p><strong>5.2 Nature of Data:</strong></p>
                    <p>The System processes official documents and may include personal or sensitive information related to transactions, senders, and recipients.</p>
                    <p><strong>5.3 Purpose and Use:</strong></p>
                    <p>Collected data is used strictly for:</p>
                    <ul>
                        <li>Managing and tracking document movement and status.</li>
                        <li>Generating reports for monitoring and auditing purposes.</li>
                        <li>Facilitating transparency and efficiency in document processing.</li>
                    </ul>
                    <p><strong>5.4 Access and Disclosure:</strong></p>
                    <p>Data will be accessible only to authorized users according to their assigned roles. We do not sell, trade, or transfer any information to third parties for commercial use. Disclosure may only occur as required by law or authorized institutional policy.</p>
                    <p><strong>5.5 Data Retention:</strong></p>
                    <p>Data will be retained for as long as necessary to fulfill the administrative purposes of the system. Once data is no longer needed, it will be securely archived, anonymized, or deleted in accordance with institutional and legal requirements.</p>

                    <h6>6. Intellectual Property</h6>
                    <p><strong>6.1 System IP:</strong></p>
                    <p>The DTS, including its source code, interface design, and software components, is the intellectual property of the DTS Development Team and DMMMSU.</p>
                    <p><strong>6.2 User-Generated Data:</strong></p>
                    <p>Documents and records uploaded by users remain the property of their respective offices or institutions. The System only facilitates their storage and tracking.</p>
                    <p><strong>6.3 User License:</strong></p>
                    <p>You are granted a limited, non-exclusive, non-transferable, and revocable license to access and use the System for authorized document tracking purposes.</p>

                    <h6>7. Disclaimer of Warranties and Limitation of Liability</h6>
                    <p><strong>7.1 "As Is" Basis:</strong></p>
                    <p><strong>THE DOCUMENT TRACKING SYSTEM IS PROVIDED ON AN "AS IS" AND "AS AVAILABLE" BASIS, WITHOUT ANY WARRANTIES OF ANY KIND, EXPRESS OR IMPLIED. We do not guarantee uninterrupted or error-free operation of the System.</strong></p>
                    <p><strong>7.2 Development Nature:</strong></p>
                    <p>You acknowledge that the DTS is an academic or developmental project and may still be undergoing testing and improvements. It is not a fully commercialized system.</p>
                    <p><strong>7.3 Limitation of Liability:</strong></p>
                    <p>To the fullest extent permitted by law, the DTS Development Team, its members, advisors, and DMMMSU shall not be held liable for any indirect, incidental, or consequential damages arising from your use or inability to use the System.</p>

                    <h6>8. Term and Termination</h6>
                    <p>This Agreement remains in effect upon your first use of the System and continues until terminated. We reserve the right to suspend or revoke your access to the System at any time, with or without cause, particularly in cases of misuse or upon completion of the project.</p>

                    <h6>9. Governing Law</h6>
                    <p>This Agreement shall be governed by and construed in accordance with the laws of the Republic of the Philippines.</p>
                    <p>Any disputes shall be brought before the competent courts of San Fernando City, La Union.</p>

                    <h6>10. Contact Information</h6>
                    <p>For questions or concerns regarding this Agreement or the System, you may contact the project team at:</p>
                    <p>📧 <a href="mailto:mjaenidoyvillar@gmail.com">mjaenidoyvillar@gmail.com</a></p>
                    <p>🏫 College of Information Technology, DMMMSU Mid-La Union Campus</p>

                    <p class="mt-4"><strong>BY USING THE DOCUMENT TRACKING SYSTEM, YOU SIGNIFY YOUR AGREEMENT TO THE FOREGOING TERMS AND CONDITIONS.</strong></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="acceptTermsBtn" disabled onclick="acceptTermsFromModal()" title="Please scroll to the bottom to enable this button">I Accept</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Login Error Modal -->
    <div class="modal fade" id="loginErrorModal" tabindex="-1" aria-labelledby="loginErrorModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="loginErrorModalLabel">Login Failed</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="loginErrorMessage">Invalid credentials</div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</body>
</html>