<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Clinic;
use Symfony\Component\HttpFoundation\Response;

class SubdomainTenant
{
    /**
     * Resolve the tenant from the subdomain.
     *
     * If a subdomain is present in the request, look up the clinic by slug.
     * Set it on the request so controllers can access it.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();
        $appDomain = config('app.domain', 'clinic0s.com');

        // Extract subdomain
        $subdomain = null;
        if (str_ends_with($host, '.' . $appDomain)) {
            $subdomain = str_replace('.' . $appDomain, '', $host);
        }

        // If no subdomain (main domain or localhost), proceed normally
        if (!$subdomain || $subdomain === 'www' || $subdomain === 'app' || $subdomain === 'admin') {
            return $next($request);
        }

        // Look up clinic by slug
        $clinic = Clinic::where('slug', $subdomain)->where('is_active', true)->first();

        if (!$clinic) {
            Log::warning('SubdomainTenant: Clinic not found for subdomain', ['subdomain' => $subdomain]);
            abort(404, 'Clinic not found');
        }

        // Bind clinic to the request
        $request->attributes->set('tenant_clinic', $clinic);
        $request->attributes->set('tenant_clinic_id', $clinic->id);

        // If user is authenticated, verify they belong to this clinic
        if ($user = $request->user()) {
            if ($user->clinic_id !== $clinic->id && $user->role !== 'super_admin') {
                Log::warning('SubdomainTenant: User does not belong to this clinic', [
                    'user_id' => $user->id,
                    'user_clinic' => $user->clinic_id,
                    'subdomain_clinic' => $clinic->id,
                ]);
                abort(403, 'You do not have access to this clinic.');
            }
        }

        return $next($request);
    }
}
