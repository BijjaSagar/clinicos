<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class PatientPortalAuth
{
    public function handle(Request $request, Closure $next)
    {
        if (!session('patient_portal_id')) {
            return redirect()->route('patient-portal.login')
                ->with('error', 'Please log in to access the patient portal.');
        }

        return $next($request);
    }
}
