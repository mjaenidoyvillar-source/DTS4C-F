<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // If no roles specified, allow any authenticated user
        if (empty($roles)) {
            return $next($request);
        }

        // Handle comma-separated roles in a single parameter
        $allowedRoles = [];
        foreach ($roles as $role) {
            $allowedRoles = array_merge($allowedRoles, explode(',', $role));
        }
        $allowedRoles = array_map('trim', $allowedRoles);

        if (!in_array($user->role, $allowedRoles)) {
            return response()->json(['message' => 'Unauthorized. Required role: ' . implode(' or ', $allowedRoles)], 403);
        }

        return $next($request);
    }
}
