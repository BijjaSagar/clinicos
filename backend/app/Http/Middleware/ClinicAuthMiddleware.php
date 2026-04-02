<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ClinicAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * Ensures the authenticated user belongs to the clinic_id present
     * in the request (route parameter, query string, or request body).
     * Super-admins (clinic_id === null) bypass this check.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Super-admins have no clinic restriction.
        if ($user->clinic_id === null && $user->role === 'super_admin') {
            return $next($request);
        }

        // Resolve the clinic_id from route parameter, query string, or body.
        $requestedClinicId = $request->route('clinic_id')
            ?? $request->query('clinic_id')
            ?? $request->input('clinic_id');

        // If no clinic_id is specified in the request, allow through
        // (individual controller gates handle further restrictions).
        if ($requestedClinicId === null) {
            return $next($request);
        }

        if ((string) $user->clinic_id !== (string) $requestedClinicId) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied: you do not belong to this clinic.',
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
