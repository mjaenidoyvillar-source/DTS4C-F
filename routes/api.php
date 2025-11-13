<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController as ApiDashboardController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\DocumentLogController;
use App\Http\Controllers\Api\DocumentTrackingController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\Owner\DocumentController as OwnerDocumentController;
use App\Http\Controllers\Handler\DocumentController as HandlerDocumentController;
use App\Http\Controllers\Auditor\AuditLogController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\DepartmentController as AdminDepartmentController;
use App\Http\Controllers\Middleware\EnsureRole;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| All API endpoints require Sanctum token authentication.
| Include token in Authorization header: Bearer {token}
|
*/

// Public routes
Route::post('/login', [AuthController::class, 'login']);

// Protected routes - require authentication (web session or Sanctum token)
Route::middleware('auth:web,sanctum')->group(function () {
    
    // Authentication
    Route::get('/user', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Dashboard endpoints
    Route::get('/dashboard/owner', [ApiDashboardController::class, 'owner'])->middleware('role:owner');
    Route::get('/dashboard/handler', [ApiDashboardController::class, 'handler'])->middleware('role:handler');

    // Documents - Shared endpoints (all roles)
    Route::prefix('documents')->group(function () {
        Route::get('/{document}/file', [DocumentController::class, 'file']);
        Route::get('/{document}/details', [DocumentController::class, 'details']);
        Route::get('/track/{qr_code}', [DocumentTrackingController::class, 'track']);
    });

    // Owner Document Routes
    Route::prefix('documents')->middleware('role:owner')->group(function () {
        Route::post('/', [OwnerDocumentController::class, 'register']); // Upload new document
        Route::delete('/{document}', [OwnerDocumentController::class, 'destroy']); // Delete document (soft delete)
        Route::get('/my-documents', [OwnerDocumentController::class, 'myDocuments']); // View uploaded documents
        // Note: /incoming, /{document}/receive and /{document}/archive moved to web.php for proper session handling
    });

    // Handler Document Routes
    Route::prefix('documents')->middleware(['auth:web,sanctum', 'role:handler'])->group(function () {
        Route::get('/assigned', [HandlerDocumentController::class, 'toProcess']); // Get assigned documents
        Route::post('/{document}/send', [HandlerDocumentController::class, 'send']); // Send to recipient handler
        Route::post('/{document}/receive', [HandlerDocumentController::class, 'receive']); // Receive from sender
        Route::post('/{document}/forward', [HandlerDocumentController::class, 'forwardToOwner']); // Forward to recipient owner
        Route::post('/{document}/reject', [HandlerDocumentController::class, 'reject']); // Reject document
    });

    // Routes for receiving documents (handler)
    Route::prefix('routes')->middleware('role:handler')->group(function () {
        Route::post('/{documentRoute}/receive', [HandlerDocumentController::class, 'receive']);
    });

    // Handler document views
    Route::prefix('handler')->middleware('role:handler')->group(function () {
        Route::get('/incoming', [HandlerDocumentController::class, 'incoming']);
        Route::get('/to-process', [HandlerDocumentController::class, 'toProcess']);
        Route::get('/on-hold', [HandlerDocumentController::class, 'onHold']);
        Route::get('/outgoing', [HandlerDocumentController::class, 'outgoing']);
        Route::get('/my-documents', [HandlerDocumentController::class, 'myDocuments']);
    });

    // Notifications
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::post('/read/{id}', [NotificationController::class, 'markAsRead']);
    });

    // Document Logs (Admin & Auditor only)
    Route::prefix('logs')->group(function () {
        Route::get('/', [DocumentLogController::class, 'index'])->middleware('role:admin,auditor');
        Route::get('/{document_id}', [DocumentLogController::class, 'show'])->middleware('role:admin,auditor');
    });

    // Audit Logs (Admin & Auditor only) - keeping for backward compatibility
    Route::get('/audit-logs', [AuditLogController::class, 'index'])->middleware('role:admin,auditor');

    // Admin User Management
    Route::prefix('admin')->middleware('role:admin')->group(function () {
        Route::get('/users', [AdminUserController::class, 'index']);
        Route::post('/users', [AdminUserController::class, 'store']);
        Route::put('/users/{user}', [AdminUserController::class, 'update']);
        Route::post('/users/{userId}/deactivate', [AdminUserController::class, 'deactivate']);
        Route::post('/users/{user}/activate', [AdminUserController::class, 'activate']);
        Route::delete('/users/{user}', [AdminUserController::class, 'destroy']);
        
        // Department Management
        Route::get('/departments', [AdminDepartmentController::class, 'index']);
        Route::post('/departments', [AdminDepartmentController::class, 'store']);
        Route::put('/departments/{department}', [AdminDepartmentController::class, 'update']);
        Route::delete('/departments/{department}', [AdminDepartmentController::class, 'destroy']);
    });

    // Departments (public endpoint for listing)
    Route::get('/departments', function () {
        $departments = \App\Models\Department::orderBy('name')->get();
        return response()->json($departments);
    });
    
    // Get owners by department
    Route::get('/departments/{department}/owners', function ($department) {
        try {
            $dept = \App\Models\Department::findOrFail($department);
            $owners = \App\Models\User::where('department_id', $dept->id)
                ->where('role', 'owner')
                ->orderBy('name')
                ->orderBy('email')
                ->get();
            
            $ownersArray = [];
            foreach ($owners as $owner) {
                $displayName = ($owner->name ?? $owner->email);
                $email = $owner->email;
                
                if (function_exists('iconv')) {
                    $displayName = @iconv('UTF-8', 'UTF-8//IGNORE', (string)$displayName) ?: (string)$email;
                    $email = @iconv('UTF-8', 'UTF-8//IGNORE', (string)$email) ?: '';
                } else {
                    $displayName = mb_convert_encoding((string)$displayName, 'UTF-8', 'UTF-8');
                    $email = mb_convert_encoding((string)$email, 'UTF-8', 'UTF-8');
                }
                
                $ownersArray[] = [
                    'id' => (int)$owner->id,
                    'name' => $displayName ?: $email,
                    'email' => $email ?: '',
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
            \Log::error('Error loading owners', [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return response()->json(['error' => 'Failed to load owners'], 500, [
                'Content-Type' => 'application/json; charset=utf-8'
            ]);
        }
    });
    
    // Get user by ID
    Route::get('/users/{user}', function ($user) {
        $user = \App\Models\User::findOrFail($user);
        return response()->json([
            'id' => $user->id,
            'name' => $user->name ?? $user->email,
            'email' => $user->email,
        ], 200, ['Content-Type' => 'application/json; charset=utf-8']);
    });

    // Profile routes
    Route::get('/profile', [App\Http\Controllers\ProfileController::class, 'show']);
    Route::put('/profile', [App\Http\Controllers\ProfileController::class, 'update']);
    Route::delete('/profile/picture', [App\Http\Controllers\ProfileController::class, 'removeProfilePicture']);
});
