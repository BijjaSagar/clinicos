<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OpdController extends Controller
{
    public function queue(Request $request)
    {
        $clinicId = auth()->user()->clinic_id;
        $date = $request->get('date', today()->toDateString());

        $appointments = Appointment::with(['patient', 'doctor'])
            ->where('clinic_id', $clinicId)
            ->whereDate('appointment_date', $date)
            ->orderBy('appointment_time')
            ->get()
            ->map(function ($appt) {
                $appt->queue_status = $appt->status === 'confirmed' ? 'waiting'
                    : ($appt->status === 'completed' ? 'done' : $appt->status);
                return $appt;
            });

        $stats = [
            'total'     => $appointments->count(),
            'waiting'   => $appointments->where('queue_status', 'waiting')->count(),
            'in_progress' => $appointments->where('status', 'in_progress')->count(),
            'completed' => $appointments->where('queue_status', 'done')->count(),
            'cancelled' => $appointments->where('status', 'cancelled')->count(),
        ];

        $doctors = User::where('clinic_id', $clinicId)
            ->where('role', 'doctor')
            ->get(['id', 'name']);

        $patients = Patient::where('clinic_id', $clinicId)
            ->orderBy('name')
            ->get(['id', 'name', 'phone']);

        return view('opd.queue', compact('appointments', 'stats', 'date', 'doctors', 'patients'));
    }

    public function updateStatus(Request $request, Appointment $appointment)
    {
        $validated = $request->validate([
            'status' => 'required|in:confirmed,in_progress,completed,cancelled,no_show',
        ]);

        $appointment->update(['status' => $validated['status']]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'status' => $validated['status']]);
        }

        return back()->with('success', 'Status updated');
    }

    public function walkin(Request $request)
    {
        $clinicId = auth()->user()->clinic_id;
        $validated = $request->validate([
            'patient_id'       => 'required|exists:patients,id',
            'doctor_id'        => 'required|exists:users,id',
            'chief_complaint'  => 'nullable|string|max:500',
            'appointment_date' => 'required|date',
            'appointment_time' => 'required',
        ]);

        $validated['clinic_id'] = $clinicId;
        $validated['status']    = 'confirmed';
        $validated['type']      = 'walkin';
        $validated['booked_by'] = auth()->id();

        Appointment::create($validated);

        return redirect()->route('opd.queue')->with('success', 'Walk-in patient added to queue');
    }
}
