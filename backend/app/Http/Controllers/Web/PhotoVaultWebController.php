<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\PatientPhoto;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class PhotoVaultWebController extends Controller
{
    public function index(Request $request)
    {
        Log::info('PhotoVaultWebController@index', ['user' => auth()->id()]);

        try {
            $clinicId = auth()->user()->clinic_id;
            
            $query = PatientPhoto::with(['patient', 'visit', 'uploadedBy'])
                ->where('clinic_id', $clinicId)
                ->orderByDesc('created_at');

            // Filter by patient
            if ($request->filled('patient_id')) {
                $query->where('patient_id', $request->patient_id);
            }

            // Filter by photo type
            if ($request->filled('type')) {
                $query->where('photo_type', $request->type);
            }

            // Filter by body region
            if ($request->filled('region')) {
                $query->where('body_region', $request->region);
            }

            $photos = $query->paginate(24);

            // Stats
            $stats = [
                'total' => PatientPhoto::where('clinic_id', $clinicId)->count(),
                'this_month' => PatientPhoto::where('clinic_id', $clinicId)
                    ->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->count(),
                'storage_kb' => PatientPhoto::where('clinic_id', $clinicId)->sum('file_size_kb') ?? 0,
                'before_after_sets' => PatientPhoto::where('clinic_id', $clinicId)
                    ->whereIn('photo_type', ['before', 'after'])
                    ->select('patient_id', 'body_region')
                    ->groupBy('patient_id', 'body_region')
                    ->havingRaw('COUNT(DISTINCT photo_type) = 2')
                    ->get()
                    ->count(),
            ];

            // Convert storage to MB
            $stats['storage_mb'] = round($stats['storage_kb'] / 1024, 1);

            // Get unique body regions for filter
            $bodyRegions = PatientPhoto::where('clinic_id', $clinicId)
                ->whereNotNull('body_region')
                ->distinct()
                ->pluck('body_region');

            // Get patients with photos for filter
            $patientsWithPhotos = Patient::whereHas('photos', fn($q) => $q->where('clinic_id', $clinicId))
                ->select('id', 'name')
                ->orderBy('name')
                ->get();

            // Recent uploads grouped by patient
            $recentByPatient = PatientPhoto::with('patient')
                ->where('clinic_id', $clinicId)
                ->select('patient_id', DB::raw('COUNT(*) as photo_count'), DB::raw('MAX(created_at) as latest'))
                ->groupBy('patient_id')
                ->orderByDesc('latest')
                ->limit(10)
                ->get();

            Log::info('PhotoVaultWebController@index success', ['photos_count' => $photos->count()]);

            return view('photo-vault.index', compact(
                'photos', 'stats', 'bodyRegions', 'patientsWithPhotos', 'recentByPatient'
            ));
            
        } catch (\Throwable $e) {
            Log::error('PhotoVaultWebController@index error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            
            return view('photo-vault.index', [
                'photos' => collect(),
                'stats' => [
                    'total' => 0, 'this_month' => 0,
                    'storage_kb' => 0, 'storage_mb' => 0,
                    'before_after_sets' => 0,
                ],
                'bodyRegions' => collect(),
                'patientsWithPhotos' => collect(),
                'recentByPatient' => collect(),
                'error' => 'Could not load photos: ' . $e->getMessage()
            ]);
        }
    }
}
