<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles  Allowed roles
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();
        
        if (!$user) {
            Log::warning('CheckRole: No authenticated user');
            return redirect()->route('login');
        }

        $userRole = $user->role ?? 'staff';
        
        Log::info('CheckRole middleware', [
            'user_id' => $user->id,
            'user_role' => $userRole,
            'allowed_roles' => $roles,
            'path' => $request->path(),
        ]);

        // Check if 'owner' is explicitly required (owner-only routes)
        $ownerOnly = in_array('owner', $roles) && count($roles) === 1;
        
        // If owner-only route, only owner can access
        if ($ownerOnly) {
            if ($userRole === 'owner') {
                return $next($request);
            }
            
            Log::warning('CheckRole: Owner-only route accessed by non-owner', [
                'user_id' => $user->id,
                'user_role' => $userRole,
            ]);
            
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Unauthorized. This action is restricted to clinic owners.'], 403);
            }
            return redirect()->route('dashboard')->with('error', 'This section is restricted to clinic owners only.');
        }

        // Owner always has access to non-owner-only routes
        if ($userRole === 'owner') {
            return $next($request);
        }

        // Check if user's role is in allowed roles
        if (in_array($userRole, $roles)) {
            return $next($request);
        }

        Log::warning('CheckRole: Access denied', [
            'user_id' => $user->id,
            'user_role' => $userRole,
            'required_roles' => $roles,
        ]);

        // Return 403 or redirect with error
        if ($request->expectsJson()) {
            return response()->json(['error' => 'Unauthorized. Insufficient permissions.'], 403);
        }

        return redirect()->route('dashboard')->with('error', 'You do not have permission to access that page.');
    }
}
