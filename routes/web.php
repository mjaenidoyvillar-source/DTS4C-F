<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Owner\DocumentController as OwnerDocumentController;
use App\Http\Controllers\Handler\DocumentController as HandlerDocumentController;
use App\Http\Controllers\Auditor\AuditLogController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\DepartmentController as AdminDepartmentController;
use App\Http\Controllers\Admin\BugReportController as AdminBugReportController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController as ApiAuthController;

Route::get('/', function () {
    return redirect()->route('login');
});

// Authentication routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Public QR code access route (for scanning QR codes without authentication)
Route::get('/documents/{document}/qr', [App\Http\Controllers\DocumentController::class, 'qrAccess'])->name('documents.qr');

// Protected routes (must be logged in)
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Profile routes
    Route::get('/profile', [App\Http\Controllers\ProfileController::class, 'show'])->name('profile.show');
    Route::put('/profile', [App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile/picture', [App\Http\Controllers\ProfileController::class, 'removeProfilePicture'])->name('profile.picture.remove');
    Route::get('/profile-picture/{filename}', [App\Http\Controllers\ProfileController::class, 'serveProfilePicture'])->name('profile.picture');

    // Shared document viewing (accessible by all roles)
    Route::get('/documents/{document}/file', [App\Http\Controllers\DocumentController::class, 'file'])->name('documents.file');
    Route::get('/documents/{document}/view', [App\Http\Controllers\DocumentController::class, 'view'])->name('documents.view');
    Route::get('/documents/{document}/details', [App\Http\Controllers\DocumentController::class, 'details'])->name('documents.details');

    // Owner document routes
    Route::post('/documents/register', [OwnerDocumentController::class, 'register'])->name('documents.register');
    Route::post('/documents/{document}/update', [OwnerDocumentController::class, 'update'])->name('documents.update');
    Route::get('/my-documents', [OwnerDocumentController::class, 'myDocuments'])->name('documents.mine');
    Route::get('/owner/released', [OwnerDocumentController::class, 'released'])->name('owner.released');
    Route::get('/owner/for-review', [OwnerDocumentController::class, 'forReview'])->name('owner.for_review');
    Route::post('/documents/{document}/accept-review', [OwnerDocumentController::class, 'acceptReview'])->name('documents.accept_review');
    Route::post('/documents/{document}/decline-review', [OwnerDocumentController::class, 'declineReview'])->name('documents.decline_review');
    Route::get('/owner/complete', [OwnerDocumentController::class, 'complete'])->name('owner.complete');
    Route::get('/owner/archived', [OwnerDocumentController::class, 'archived'])->name('owner.archived');
    Route::get('/owner/deleted', [OwnerDocumentController::class, 'deleted'])->name('owner.deleted');
    Route::post('/documents/{document}/archive', [OwnerDocumentController::class, 'archive'])->name('documents.archive');
    Route::post('/documents/{document}/unarchive', [OwnerDocumentController::class, 'unarchive'])->name('documents.unarchive');
    Route::delete('/documents/{document}', [OwnerDocumentController::class, 'destroy'])->name('documents.destroy');
    
    // API-style routes for owner documents (needs web middleware for session)
    Route::get('/api/documents/incoming', [OwnerDocumentController::class, 'incoming'])->middleware('role:owner');
    // Change owner receive path to avoid conflict with handler receive
    Route::post('/api/owner/documents/{document}/receive', [OwnerDocumentController::class, 'receive'])->middleware('role:owner');
    Route::get('/api/documents/received', [OwnerDocumentController::class, 'receivedList'])->middleware('role:owner');
    Route::get('/api/documents/archived', [OwnerDocumentController::class, 'archived'])->middleware('role:owner');
    Route::post('/api/documents/{document}/archive', [OwnerDocumentController::class, 'archive'])->middleware('role:owner');
    Route::post('/api/documents/{document}/unarchive', [OwnerDocumentController::class, 'unarchive'])->middleware('role:owner');
    
    // Handler document routes
    Route::post('/documents/{document}/send', [HandlerDocumentController::class, 'send'])->name('documents.send');
    Route::post('/routes/{documentRoute}/receive', [HandlerDocumentController::class, 'receive'])->name('routes.receive');
    Route::post('/documents/{document}/hold', [HandlerDocumentController::class, 'hold'])->name('documents.hold');
    Route::post('/documents/{document}/resume', [HandlerDocumentController::class, 'resume'])->name('documents.resume');
    Route::post('/documents/{document}/forward-to-owner', [HandlerDocumentController::class, 'forwardToOwner'])->name('documents.forward_to_owner');
    Route::post('/documents/{document}/send-to-owner', [HandlerDocumentController::class, 'sendToOwner'])->name('documents.send_to_owner');
    Route::get('/handler/incoming', [HandlerDocumentController::class, 'incoming'])->name('handler.incoming');
    Route::get('/handler/to-process', [HandlerDocumentController::class, 'toProcess'])->name('handler.to_process');
    Route::get('/handler/on-hold', [HandlerDocumentController::class, 'onHold'])->name('handler.on_hold');
    Route::get('/handler/outgoing', [HandlerDocumentController::class, 'outgoing'])->name('handler.outgoing');
    Route::get('/handler/my-documents', [HandlerDocumentController::class, 'myDocuments'])->name('handler.my_documents');

    // Auditor logs - Only accessible by admin and auditor
    Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit.logs');
    Route::get('/audit-logs/export', [AuditLogController::class, 'exportAuditLogs'])->name('audit.logs.export');
    
    // Admin activity logs - All system activities
    Route::get('/admin/activity-logs', [AuditLogController::class, 'activityLogs'])->middleware('role:admin')->name('admin.activity-logs');
    Route::get('/admin/activity-logs/export', [AuditLogController::class, 'exportActivityLogs'])->middleware('role:admin')->name('admin.activity-logs.export');

    // Admin users
    Route::get('/admin/users', [AdminUserController::class, 'index'])->name('admin.users.index');
    Route::post('/admin/users', [AdminUserController::class, 'store'])->name('admin.users.store');
    Route::put('/admin/users/{user}', [AdminUserController::class, 'update'])->name('admin.users.update');
    Route::post('/admin/users/{userId}/deactivate', [AdminUserController::class, 'deactivate'])->name('admin.users.deactivate');
    Route::post('/admin/users/{user}/activate', [AdminUserController::class, 'activate'])->name('admin.users.activate');
    Route::delete('/admin/users/{user}', [AdminUserController::class, 'destroy'])->name('admin.users.destroy');
    
    // Admin departments
    Route::get('/admin/departments', [AdminDepartmentController::class, 'index'])->name('admin.departments.index');
    Route::post('/admin/departments', [AdminDepartmentController::class, 'store'])->name('admin.departments.store');
    Route::put('/admin/departments/{department}', [AdminDepartmentController::class, 'update'])->name('admin.departments.update');
    Route::delete('/admin/departments/{department}', [AdminDepartmentController::class, 'destroy'])->name('admin.departments.destroy');
    
    // Admin bug reports
    Route::get('/admin/bug-reports', [AdminBugReportController::class, 'index'])->middleware('role:admin')->name('admin.bug-reports.index');
    Route::get('/admin/bug-reports/{bugReport}', [AdminBugReportController::class, 'show'])->middleware('role:admin')->name('admin.bug-reports.show');
    Route::post('/admin/bug-reports/{bugReport}/status', [AdminBugReportController::class, 'updateStatus'])->middleware('role:admin')->name('admin.bug-reports.update-status');
    Route::delete('/admin/bug-reports/{bugReport}', [AdminBugReportController::class, 'destroy'])->middleware('role:admin')->name('admin.bug-reports.destroy');
    
    // Get owners by department (for document creation form)
    Route::get('/api/departments/{department}/owners', function ($department) {
        try {
            $dept = \App\Models\Department::findOrFail($department);
            $owners = \App\Models\User::where('department_id', $dept->id)
                ->where('role', 'owner')
                ->orderBy('name')
                ->orderBy('email')
                ->get();
            
            // Map to include display name (prefer name, then email)
            $ownersArray = [];
            foreach ($owners as $owner) {
                $email = trim((string)$owner->email);
                
                // Prefer name, then email for display
                $displayName = ($owner->name ?? $email);
                
                // Remove any control characters and ensure valid UTF-8
                $email = preg_replace('/[\x00-\x1F\x7F]/', '', $email);
                $displayName = preg_replace('/[\x00-\x1F\x7F]/', '', (string)$displayName);
                
                // Final UTF-8 validation
                if (!mb_check_encoding($email, 'UTF-8')) {
                    $email = mb_convert_encoding($email, 'UTF-8', 'UTF-8');
                }
                if (!mb_check_encoding($displayName, 'UTF-8')) {
                    $displayName = mb_convert_encoding($displayName, 'UTF-8', 'UTF-8');
                }
                
                $ownersArray[] = [
                    'id' => (int)$owner->id,
                    'name' => $displayName,
                    'email' => $email,
                ];
            }
            
            return response()->json($ownersArray, 200, [
                'Content-Type' => 'application/json; charset=utf-8'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'Department not found'], 404, [
                'Content-Type' => 'application/json; charset=utf-8'
            ]);
        } catch (\Exception $e) {
            // Log error safely without potentially malformed strings
            try {
                $errorInfo = [
                    'exception' => get_class($e),
                    'file' => basename($e->getFile()),
                    'line' => $e->getLine(),
                ];
                \Log::error('Error loading owners', $errorInfo);
            } catch (\Exception $logError) {
                // If logging also fails, skip it
            }
            
            return response()->json(['error' => 'Failed to load owners'], 500, [
                'Content-Type' => 'application/json; charset=utf-8'
            ]);
        }
    })->name('api.departments.owners');
});

// API-style login via web middleware to also establish session
Route::post('/api/login', [ApiAuthController::class, 'login'])->name('api.login');

use Illuminate\Support\Facades\Mail;
Route::get('/mail-test', function () {
    Mail::raw('This is a test email from Laravel DTS using Gmail SMTP.', function ($message) {
        $message->to('yourrealemail@gmail.com')  // <-- put a real email here
                ->subject('SMTP Test from Laravel');
    });

    return 'Test email sent!';
});