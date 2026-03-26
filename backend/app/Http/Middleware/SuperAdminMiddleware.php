<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class SuperAdminMiddleware
{
    /**
     * Handle an incoming request.
     * Only allows users with role 'super_admin' to access.
     */
    public function handle(Request $request, Closure $next): Response
    {
        Log::info('SuperAdminMiddleware check', [
            'user_id' => auth()->id(),
            'role' => auth()->user()?->role,
        ]);

        if (!auth()->check()) {
            return redirect()->route('admin.login');
        }

        if (auth()->user()->role !== 'super_admin') {
            Log::warning('Unauthorized super admin access attempt', [
                'user_id' => auth()->id(),
                'role' => auth()->user()->role,
            ]);
            
            abort(403, 'Access denied. Super Admin privileges required.');
        }

        return $next($request);
    }
}
