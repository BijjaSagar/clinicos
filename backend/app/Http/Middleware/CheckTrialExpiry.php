<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckTrialExpiry
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (!$user) return $next($request);

        if ($user->role === 'super_admin' || !$user->clinic_id) return $next($request);
        if ($user->role !== 'owner') return $next($request);

        $clinic = $user->clinic;
        if (!$clinic) return $next($request);

        if ($clinic->plan && $clinic->plan !== 'trial') return $next($request);

        if ($clinic->trial_ends_at && $clinic->trial_ends_at->isPast()) {
            $allowedRoutes = ['subscription.expired', 'subscription.plans', 'subscription.checkout', 'logout'];
            if (in_array($request->route()?->getName(), $allowedRoutes)) {
                return $next($request);
            }
            return redirect()->route('subscription.expired');
        }

        return $next($request);
    }
}
