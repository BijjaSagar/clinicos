<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Prescription;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class PrescriptionWebController extends Controller
{
    public function index(Request $request)
    {
        Log::info('PrescriptionWebController@index', ['user' => auth()->id()]);

        try {
            $clinicId = auth()->user()->clinic_id;
            
            $query = Prescription::with(['patient', 'doctor', 'drugs', 'visit'])
                ->where('clinic_id', $clinicId)
                ->orderByDesc('created_at');

            // Filter by patient
            if ($request->filled('patient_id')) {
                $query->where('patient_id', $request->patient_id);
            }

            // Filter by doctor
            if ($request->filled('doctor_id')) {
                $query->where('doctor_id', $request->doctor_id);
            }

            // Filter by date range
            if ($request->filled('from')) {
                $query->whereDate('created_at', '>=', $request->from);
            }
            if ($request->filled('to')) {
                $query->whereDate('created_at', '<=', $request->to);
            }

            // Filter by WhatsApp sent status
            if ($request->filled('sent')) {
                if ($request->sent === 'yes') {
                    $query->whereNotNull('whatsapp_sent_at');
                } else {
                    $query->whereNull('whatsapp_sent_at');
                }
            }

            $prescriptions = $query->paginate(20);

            // Stats
            $today = now()->toDateString();
            $weekStart = now()->startOfWeek()->toDateString();
            $monthStart = now()->startOfMonth()->toDateString();

            $stats = [
                'today' => Prescription::where('clinic_id', $clinicId)
                    ->whereDate('created_at', $today)
                    ->count(),
                'week' => Prescription::where('clinic_id', $clinicId)
                    ->whereDate('created_at', '>=', $weekStart)
                    ->count(),
                'sent_via_whatsapp' => Prescription::where('clinic_id', $clinicId)
                    ->whereMonth('created_at', now()->month)
                    ->whereNotNull('whatsapp_sent_at')
                    ->count(),
                'templates' => 0, // Placeholder - templates would be a separate feature
            ];

            // Get patients for filter
            $patients = Patient::whereHas('prescriptions', fn($q) => $q->where('clinic_id', $clinicId))
                ->select('id', 'name')
                ->orderBy('name')
                ->get();

            // Get doctors for filter
            $doctors = \App\Models\User::whereHas('prescriptions', fn($q) => $q->where('clinic_id', $clinicId))
                ->select('id', 'name')
                ->orderBy('name')
                ->get();

            // Top prescribed drugs this month
            $topDrugs = DB::table('prescription_drugs')
                ->join('prescriptions', 'prescription_drugs.prescription_id', '=', 'prescriptions.id')
                ->where('prescriptions.clinic_id', $clinicId)
                ->whereMonth('prescriptions.created_at', now()->month)
                ->select('prescription_drugs.drug_name', DB::raw('COUNT(*) as count'))
                ->groupBy('prescription_drugs.drug_name')
                ->orderByDesc('count')
                ->limit(5)
                ->get();

            Log::info('PrescriptionWebController@index success', ['prescriptions_count' => $prescriptions->count()]);

            return view('prescriptions.index', compact(
                'prescriptions', 'stats', 'patients', 'doctors', 'topDrugs'
            ));
            
        } catch (\Throwable $e) {
            Log::error('PrescriptionWebController@index error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            
            return view('prescriptions.index', [
                'prescriptions' => collect(),
                'stats' => [
                    'today' => 0, 'week' => 0,
                    'sent_via_whatsapp' => 0, 'templates' => 0,
                ],
                'patients' => collect(),
                'doctors' => collect(),
                'topDrugs' => collect(),
                'error' => 'Could not load prescriptions: ' . $e->getMessage()
            ]);
        }
    }
}
