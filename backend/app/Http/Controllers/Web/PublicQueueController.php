<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class PublicQueueController extends Controller
{
    /**
     * Public OPD queue display — no auth required.
     * URL: /queue/{clinicSlug}
     */
    public function show(string $clinicSlug)
    {
        $clinic = DB::table('clinics')
            ->where('slug', $clinicSlug)
            ->where('is_active', true)
            ->first();

        abort_if(!$clinic, 404, 'Clinic not found.');

        $today = today()->toDateString();

        // Currently serving (in_consultation, lowest token)
        $serving = DB::table('appointments')
            ->where('clinic_id', $clinic->id)
            ->whereDate('scheduled_at', $today)
            ->where('status', 'in_consultation')
            ->whereNotNull('queue_token')
            ->orderBy('queue_token')
            ->select('queue_token', 'scheduled_at')
            ->first();

        // Waiting patients (checked_in)
        $waiting = DB::table('appointments')
            ->join('patients', 'appointments.patient_id', '=', 'patients.id')
            ->where('appointments.clinic_id', $clinic->id)
            ->whereDate('appointments.scheduled_at', $today)
            ->where('appointments.status', 'checked_in')
            ->whereNotNull('appointments.queue_token')
            ->orderBy('appointments.queue_token')
            ->select('appointments.queue_token', 'patients.name as patient_name', 'appointments.scheduled_at')
            ->get();

        // Stats
        $completedToday = DB::table('appointments')
            ->where('clinic_id', $clinic->id)
            ->whereDate('scheduled_at', $today)
            ->where('status', 'completed')
            ->count();

        $totalToday = DB::table('appointments')
            ->where('clinic_id', $clinic->id)
            ->whereDate('scheduled_at', $today)
            ->whereNotIn('status', ['cancelled', 'no_show'])
            ->count();

        return view('public.queue', compact(
            'clinic', 'serving', 'waiting', 'completedToday', 'totalToday'
        ));
    }
}
