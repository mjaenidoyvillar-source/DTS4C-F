<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureRole::class,
        ]);
        
        // Exempt API login route from CSRF verification
        $middleware->validateCsrfTokens(except: [
            'api/login',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Handle validation exceptions properly for API requests
        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $e->errors()
                ], 422, ['Content-Type' => 'application/json; charset=utf-8']);
            }
        });

        // Handle ModelNotFoundException for API requests
        $exceptions->render(function (\Illuminate\Database\Eloquent\ModelNotFoundException $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                \Log::error('Model not found in API request', [
                    'message' => $e->getMessage(),
                    'model' => $e->getModel(),
                    'ids' => $e->getIds(),
                    'path' => $request->path(),
                ]);
                return response()->json([
                    'message' => 'Resource not found',
                    'error' => config('app.debug') ? $e->getMessage() : 'The requested resource could not be found',
                ], 404, ['Content-Type' => 'application/json; charset=utf-8']);
            }
        });

        // Handle MySQL "server has gone away" errors - attempt reconnection
        $exceptions->render(function (\PDOException $e, $request) {
            if (str_contains($e->getMessage(), 'MySQL server has gone away') || 
                str_contains($e->getMessage(), '2006')) {
                try {
                    // Attempt to reconnect
                    \Illuminate\Support\Facades\DB::reconnect();
                    
                    // Retry the request if it's a GET request (safe to retry)
                    if ($request->isMethod('GET')) {
                        return redirect($request->fullUrl());
                    }
                } catch (\Exception $reconnectError) {
                    \Log::error('Failed to reconnect to database', [
                        'original_error' => $e->getMessage(),
                        'reconnect_error' => $reconnectError->getMessage(),
                    ]);
                }
                
                // Return error response
                if ($request->expectsJson() || $request->is('api/*')) {
                    return response()->json([
                        'message' => 'Database connection error. Please try again.',
                        'error' => config('app.debug') ? $e->getMessage() : 'A database error occurred',
                    ], 503, ['Content-Type' => 'application/json; charset=utf-8']);
                }
            }
        });

        // Handle other exceptions for API requests
        $exceptions->render(function (\Throwable $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                // Don't override validation exceptions
                if ($e instanceof \Illuminate\Validation\ValidationException) {
                    return null; // Let the validation handler above handle it
                }
                
                // Don't override ModelNotFoundException
                if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
                    return null; // Let the ModelNotFoundException handler above handle it
                }
                
                // Don't override PDOException (handled above)
                if ($e instanceof \PDOException) {
                    return null; // Let the PDOException handler above handle it
                }
                
                // Log the error
                \Log::error('Unhandled exception in API request', [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                    'path' => $request->path(),
                ]);
                
                // Return clean JSON for other errors
                $status = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;
                return response()->json([
                    'message' => 'An error occurred',
                    'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
                ], $status, ['Content-Type' => 'application/json; charset=utf-8']);
            }
        });

        // Automatically log all exceptions to bug_reports table
        $exceptions->report(function (\Throwable $e) {
            // Skip certain exceptions that we don't want to log
            if ($e instanceof \Illuminate\Validation\ValidationException) {
                return; // Don't log validation errors
            }
            
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
                return; // Don't log 404 errors
            }
            
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException) {
                return; // Don't log method not allowed errors
            }

            try {
                // Get request from container if available
                $request = request();
                if (!$request) {
                    return; // No request available (e.g., console command)
                }

                // Determine severity based on exception type
                $severity = 'error';
                if ($e instanceof \Error || $e instanceof \ParseError) {
                    $severity = 'critical';
                } elseif ($e instanceof \PDOException || $e instanceof \Illuminate\Database\QueryException) {
                    $severity = 'critical';
                }

                // Get request information
                $url = $request->fullUrl();
                $method = $request->method();
                $ipAddress = $request->ip();
                $userAgent = $request->userAgent();
                $userId = auth()->id();
                
                // Get request data (limit size to avoid storing too much)
                $requestData = [];
                if ($request->isMethod('POST') || $request->isMethod('PUT') || $request->isMethod('PATCH')) {
                    $requestData = $request->except(['password', 'password_confirmation', '_token']);
                    // Limit request data size
                    $requestDataJson = json_encode($requestData);
                    if (strlen($requestDataJson) > 5000) {
                        $requestData = ['_truncated' => 'Request data too large'];
                    }
                }

                // Check if similar error exists (same error type, file, line, and similar message)
                $existingReport = \App\Models\BugReport::where('error_type', get_class($e))
                    ->where('file', $e->getFile())
                    ->where('line', $e->getLine())
                    ->where('status', 'open')
                    ->whereRaw('LEFT(message, 200) = ?', [substr($e->getMessage(), 0, 200)])
                    ->first();

                if ($existingReport) {
                    // Increment occurrence count for duplicate errors
                    $existingReport->incrementOccurrence();
                } else {
                    // Create new bug report
                    \App\Models\BugReport::create([
                        'error_type' => get_class($e),
                        'severity' => $severity,
                        'message' => $e->getMessage(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => $e->getTraceAsString(),
                        'url' => $url,
                        'method' => $method,
                        'user_id' => $userId,
                        'ip_address' => $ipAddress,
                        'user_agent' => $userAgent,
                        'request_data' => $requestData,
                        'status' => 'open',
                        'occurrence_count' => 1,
                    ]);
                }
            } catch (\Exception $logError) {
                // If logging to database fails, fall back to Laravel's log
                \Log::error('Failed to log bug report to database', [
                    'original_error' => $e->getMessage(),
                    'logging_error' => $logError->getMessage(),
                ]);
            }
        });
    })->create();
