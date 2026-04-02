<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\InAppNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = InAppNotification::forUser(auth()->id())
            ->orderByDesc('created_at')
            ->paginate(30);

        return view('notifications.index', compact('notifications'));
    }

    public function markAllRead(Request $request)
    {
        InAppNotification::where('user_id', auth()->id())
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'All notifications marked as read.');
    }

    /** Item 13: Cmd+K global search */
    public function globalSearch(Request $request)
    {
        $q = trim($request->input('q', ''));
        if (strlen($q) < 2) {
            return response()->json([]);
        }

        $clinicId = auth()->user()->clinic_id;
        $results  = [];

        // Patients
        $patients = DB::table('patients')
            ->where('clinic_id', $clinicId)
            ->where(fn($sq) => $sq->where('name', 'like', "%{$q}%")->orWhere('phone', 'like', "%{$q}%")->orWhere('patient_id', 'like', "%{$q}%"))
            ->select('id', 'name', 'phone', 'patient_id')
            ->limit(5)->get();

        foreach ($patients as $p) {
            $results[] = [
                'type'     => 'patient',
                'label'    => $p->name,
                'sublabel' => $p->phone . ($p->patient_id ? ' · ' . $p->patient_id : ''),
                'url'      => route('patients.show', $p->id),
            ];
        }

        // Invoices
        $invoices = DB::table('invoices')
            ->join('patients', 'invoices.patient_id', '=', 'patients.id')
            ->where('invoices.clinic_id', $clinicId)
            ->where(fn($sq) => $sq->where('invoices.invoice_number', 'like', "%{$q}%")->orWhere('patients.name', 'like', "%{$q}%"))
            ->select('invoices.id', 'invoices.invoice_number', 'invoices.total', 'patients.name as patient_name')
            ->limit(4)->get();

        foreach ($invoices as $inv) {
            $results[] = [
                'type'     => 'invoice',
                'label'    => 'Invoice #' . $inv->invoice_number,
                'sublabel' => $inv->patient_name . ' · ₹' . number_format($inv->total, 2),
                'url'      => route('billing.show', $inv->id),
            ];
        }

        // Appointments
        $appointments = DB::table('appointments')
            ->join('patients', 'appointments.patient_id', '=', 'patients.id')
            ->where('appointments.clinic_id', $clinicId)
            ->where('patients.name', 'like', "%{$q}%")
            ->whereDate('appointments.scheduled_at', '>=', today()->subDays(7))
            ->select('appointments.id', 'appointments.scheduled_at', 'appointments.status', 'patients.name as patient_name')
            ->orderByDesc('appointments.scheduled_at')
            ->limit(3)->get();

        foreach ($appointments as $apt) {
            $results[] = [
                'type'     => 'appointment',
                'label'    => $apt->patient_name,
                'sublabel' => \Carbon\Carbon::parse($apt->scheduled_at)->format('d M Y, h:i A') . ' · ' . ucfirst($apt->status),
                'url'      => route('schedule'),
            ];
        }

        return response()->json($results);
    }
}
