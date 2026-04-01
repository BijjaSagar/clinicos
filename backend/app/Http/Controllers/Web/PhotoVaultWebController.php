<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\PatientPhoto;
use App\Models\Patient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PhotoVaultWebController extends Controller
{
    /**
     * Body regions for tagging photos
     */
    private array $bodyRegions = [
        'face' => ['Forehead', 'Cheeks', 'Nose', 'Chin', 'Jaw', 'Perioral', 'Periorbital', 'Full Face'],
        'scalp' => ['Vertex', 'Frontal', 'Temporal', 'Occipital', 'Full Scalp'],
        'neck' => ['Anterior Neck', 'Posterior Neck', 'Lateral Neck'],
        'torso' => ['Chest', 'Upper Back', 'Lower Back', 'Abdomen', 'Flanks'],
        'upper_limbs' => ['Shoulder', 'Upper Arm', 'Elbow', 'Forearm', 'Wrist', 'Hand', 'Palm', 'Fingers', 'Nails'],
        'lower_limbs' => ['Thigh', 'Knee', 'Shin', 'Calf', 'Ankle', 'Foot', 'Sole', 'Toes', 'Toenails'],
        'genitals' => ['Genital Area'],
        'other' => ['Axilla', 'Groin', 'Buttocks', 'Other'],
    ];

    public function index(Request $request): View
    {
        $clinicId = auth()->user()->clinic_id;
        Log::info('PhotoVaultWebController@index', ['user' => auth()->id(), 'clinic_id' => $clinicId]);

        try {
            $query = PatientPhoto::with(['patient', 'visit', 'uploadedBy'])
                ->where('clinic_id', $clinicId)
                ->orderByDesc('created_at');

            if ($request->filled('patient_id')) {
                $query->where('patient_id', $request->patient_id);
            }

            if ($request->filled('type')) {
                $query->where('photo_type', $request->type);
            }

            if ($request->filled('region')) {
                $query->where('body_region', $request->region);
            }

            $photos = $query->paginate(24);

            $stats = [
                'total' => PatientPhoto::where('clinic_id', $clinicId)->count(),
                'this_month' => PatientPhoto::where('clinic_id', $clinicId)
                    ->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->count(),
                'storage_kb' => PatientPhoto::where('clinic_id', $clinicId)->sum('file_size_kb') ?? 0,
                'before_after_sets' => $this->getBeforeAfterSetsCount($clinicId),
            ];

            $stats['storage_mb'] = round($stats['storage_kb'] / 1024, 1);

            $bodyRegions = PatientPhoto::where('clinic_id', $clinicId)
                ->whereNotNull('body_region')
                ->distinct()
                ->pluck('body_region');

            $patientsWithPhotos = Patient::whereHas('photos', fn($q) => $q->where('clinic_id', $clinicId))
                ->select('id', 'name')
                ->orderBy('name')
                ->get();

            $recentByPatient = PatientPhoto::with('patient')
                ->where('clinic_id', $clinicId)
                ->select('patient_id', DB::raw('COUNT(*) as photo_count'), DB::raw('MAX(created_at) as latest'))
                ->groupBy('patient_id')
                ->orderByDesc('latest')
                ->limit(10)
                ->get();

            $beforeAfterPairs = $this->getBeforeAfterPairs($clinicId);

            Log::info('PhotoVaultWebController@index success', ['photos_count' => $photos->count()]);

            return view('photo-vault.index', compact(
                'photos', 'stats', 'bodyRegions', 'patientsWithPhotos', 
                'recentByPatient', 'beforeAfterPairs'
            ));
            
        } catch (\Throwable $e) {
            Log::error('PhotoVaultWebController@index error', ['error' => $e->getMessage()]);
            
            return view('photo-vault.index', [
                'photos' => collect(),
                'stats' => ['total' => 0, 'this_month' => 0, 'storage_kb' => 0, 'storage_mb' => 0, 'before_after_sets' => 0],
                'bodyRegions' => collect(),
                'patientsWithPhotos' => collect(),
                'recentByPatient' => collect(),
                'beforeAfterPairs' => collect(),
                'error' => 'Could not load photos: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Upload a new photo
     */
    public function upload(Request $request): JsonResponse
    {
        $clinicId = auth()->user()->clinic_id;
        Log::info('PhotoVaultWebController@upload', ['clinic_id' => $clinicId]);

        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'photo' => 'required|image|mimes:jpeg,png,jpg,webp|max:10240',
            'photo_type' => 'required|in:before,after,progress,clinical,other',
            'body_region' => 'nullable|string|max:100',
            'body_subregion' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:500',
            'visit_id' => 'nullable|exists:visits,id',
            'pair_with_photo_id' => 'nullable|exists:patient_photos,id',
            'consent_confirmed' => 'required|accepted',
        ], [
            'consent_confirmed.required' => 'Patient photo consent is required before uploading clinical photos.',
            'consent_confirmed.accepted' => 'You must confirm that patient consent has been obtained for this photo.',
        ]);

        $patient = Patient::findOrFail($validated['patient_id']);
        if ($patient->clinic_id !== $clinicId) {
            Log::warning('PhotoVaultWebController: Upload blocked due to cross-clinic access', [
                'clinic_id' => $clinicId,
                'patient_id' => $patient->id,
                'patient_clinic_id' => $patient->clinic_id,
            ]);
            return response()->json(['success' => false, 'error' => 'Unauthorized'], 403);
        }

        if (!$patient->photo_consent_given) {
            $patient->update([
                'photo_consent_given' => true,
                'photo_consent_at' => now(),
            ]);
            Log::info('PhotoVaultWebController: Photo consent recorded for patient', [
                'patient_id' => $patient->id,
                'consented_at' => now()->toIso8601String(),
                'consented_by_user' => auth()->id(),
            ]);
        }

        try {
            $file = $request->file('photo');
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $encrypt = (bool) config('services.photo_vault.encrypt_uploads', false);
            Log::info('PhotoVaultWebController: upload path decision', [
                'encrypt_uploads' => $encrypt,
                'patient_id' => $patient->id,
            ]);

            if ($encrypt) {
                $raw = file_get_contents($file->getRealPath());
                $cipher = Crypt::encryptString($raw);
                $path = 'patient-photos/' . $validated['patient_id'] . '/' . $filename . '.enc';
                Storage::disk('local')->put($path, $cipher);
                $disk = 'local';
                $isEncrypted = true;
            } else {
                $path = $file->storeAs('patient-photos/' . $validated['patient_id'], $filename, 'public');
                $disk = 'public';
                $isEncrypted = false;
            }

            Log::info('PhotoVaultWebController: Photo file stored', [
                'patient_id' => $patient->id,
                'path' => $path,
                'disk' => $disk,
                'encrypted' => $isEncrypted,
                'size_kb' => round($file->getSize() / 1024),
            ]);

            $photo = PatientPhoto::create([
                'clinic_id' => $clinicId,
                'patient_id' => $validated['patient_id'],
                'visit_id' => $validated['visit_id'] ?? null,
                'uploaded_by' => auth()->id(),
                'photo_type' => $validated['photo_type'],
                'body_region' => $validated['body_region'] ?? null,
                'body_subregion' => $validated['body_subregion'] ?? null,
                'description' => $validated['description'] ?? null,
                'file_path' => $path,
                'file_name' => $file->getClientOriginalName(),
                'storage_disk' => $disk,
                's3_key' => $path,
                's3_bucket' => $isEncrypted ? 'local_encrypted' : 'public',
                'file_size_kb' => round($file->getSize() / 1024),
                'mime_type' => $file->getMimeType(),
                'pair_id' => $validated['pair_with_photo_id'] ?? null,
                'consent_obtained' => true,
                'consent_at' => now(),
                'is_encrypted' => $isEncrypted,
            ]);

            if (!empty($validated['pair_with_photo_id'])) {
                PatientPhoto::where('id', $validated['pair_with_photo_id'])->update(['pair_id' => $photo->id]);
            }

            Log::info('PhotoVaultWebController: Photo uploaded', ['photo_id' => $photo->id]);

            $url = $isEncrypted
                ? route('patients.view-photo', ['patient' => $patient->id, 'photo' => $photo->id])
                : Storage::url($path);

            Log::info('PhotoVaultWebController: upload response URL', ['photo_id' => $photo->id, 'url' => $url]);

            return response()->json([
                'success' => true,
                'photo' => $photo,
                'url' => $url,
            ]);

        } catch (\Throwable $e) {
            Log::error('PhotoVaultWebController@upload error', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update photo metadata
     */
    public function update(Request $request, PatientPhoto $photo): JsonResponse
    {
        $clinicId = auth()->user()->clinic_id;
        Log::info('PhotoVaultWebController@update', ['photo_id' => $photo->id]);

        if ($photo->clinic_id !== $clinicId) {
            return response()->json(['success' => false, 'error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'photo_type' => 'nullable|in:before,after,progress,clinical,other',
            'body_region' => 'nullable|string|max:100',
            'body_subregion' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:500',
            'is_favorite' => 'nullable|boolean',
        ]);

        $photo->update($validated);

        return response()->json(['success' => true, 'photo' => $photo]);
    }

    /**
     * Delete a photo
     */
    public function destroy(PatientPhoto $photo): JsonResponse
    {
        $clinicId = auth()->user()->clinic_id;
        Log::info('PhotoVaultWebController@destroy', ['photo_id' => $photo->id]);

        if ($photo->clinic_id !== $clinicId) {
            return response()->json(['success' => false, 'error' => 'Unauthorized'], 403);
        }

        $rel = $photo->file_path ?: $photo->s3_key;
        if ($rel) {
            if ($photo->is_encrypted && ($photo->storage_disk ?? '') === 'local') {
                Storage::disk('local')->delete($rel);
                Log::info('PhotoVaultWebController: deleted encrypted local file', ['path' => $rel]);
            } elseif (Storage::disk('public')->exists($rel)) {
                Storage::disk('public')->delete($rel);
                Log::info('PhotoVaultWebController: deleted public file', ['path' => $rel]);
            }
        }

        $photo->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Get comparison view for a patient
     */
    public function comparison(Request $request, Patient $patient): View
    {
        $clinicId = auth()->user()->clinic_id;
        Log::info('PhotoVaultWebController@comparison', ['patient_id' => $patient->id]);

        $photos = PatientPhoto::where('clinic_id', $clinicId)
            ->where('patient_id', $patient->id)
            ->orderBy('body_region')
            ->orderByDesc('created_at')
            ->get();

        $groupedByRegion = $photos->groupBy('body_region');

        $beforePhotos = $photos->where('photo_type', 'before');
        $afterPhotos = $photos->where('photo_type', 'after');
        $progressPhotos = $photos->where('photo_type', 'progress');

        return view('photo-vault.comparison', compact('patient', 'photos', 'groupedByRegion', 'beforePhotos', 'afterPhotos', 'progressPhotos'));
    }

    /**
     * Get body regions for tagging
     */
    public function getBodyRegions(): JsonResponse
    {
        return response()->json($this->bodyRegions);
    }

    /**
     * Create a before/after pair
     */
    public function createPair(Request $request): JsonResponse
    {
        Log::info('PhotoVaultWebController@createPair');

        $validated = $request->validate([
            'before_photo_id' => 'required|exists:patient_photos,id',
            'after_photo_id' => 'required|exists:patient_photos,id',
        ]);

        $before = PatientPhoto::find($validated['before_photo_id']);
        $after = PatientPhoto::find($validated['after_photo_id']);

        if ($before->patient_id !== $after->patient_id) {
            return response()->json(['success' => false, 'error' => 'Photos must belong to the same patient'], 400);
        }

        $before->update(['photo_type' => 'before', 'pair_id' => $after->id]);
        $after->update(['photo_type' => 'after', 'pair_id' => $before->id]);

        return response()->json(['success' => true, 'message' => 'Pair created']);
    }

    /**
     * Get timeline of photos for a patient
     */
    public function timeline(Patient $patient): JsonResponse
    {
        $clinicId = auth()->user()->clinic_id;
        Log::info('PhotoVaultWebController@timeline', ['patient_id' => $patient->id]);

        $photos = PatientPhoto::where('clinic_id', $clinicId)
            ->where('patient_id', $patient->id)
            ->orderBy('created_at')
            ->get()
            ->map(function ($photo) {
                $url = ($photo->is_encrypted && ($photo->storage_disk ?? '') === 'local')
                    ? route('patients.view-photo', ['patient' => $photo->patient_id, 'photo' => $photo->id])
                    : Storage::url($photo->file_path ?: $photo->s3_key);

                Log::debug('PhotoVaultWebController@timeline url', ['photo_id' => $photo->id, 'encrypted' => $photo->is_encrypted]);

                return [
                    'id' => $photo->id,
                    'url' => $url,
                    'photo_type' => $photo->photo_type,
                    'body_region' => $photo->body_region,
                    'date' => $photo->created_at->format('d M Y'),
                    'description' => $photo->description,
                ];
            });

        return response()->json($photos);
    }

    /**
     * Get before/after pairs count
     */
    private function getBeforeAfterSetsCount(int $clinicId): int
    {
        return PatientPhoto::where('clinic_id', $clinicId)
            ->whereIn('photo_type', ['before', 'after'])
            ->whereNotNull('pair_id')
            ->count() / 2;
    }

    /**
     * Record or check patient photo consent status
     */
    public function recordConsent(Request $request): JsonResponse
    {
        Log::info('PhotoVaultWebController@recordConsent incoming', [
            'patient_id' => $request->input('patient_id'),
            'consent_given' => $request->input('consent_given'),
            'has_signature' => $request->filled('signature_image'),
            'signature_len' => $request->filled('signature_image') ? strlen((string) $request->input('signature_image')) : 0,
            'content_type' => $request->header('Content-Type'),
        ]);

        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'consent_given' => 'required|boolean',
            'signature_image' => 'nullable|string|max:650000',
        ]);

        $clinicId = auth()->user()->clinic_id;
        $patient = Patient::findOrFail($validated['patient_id']);

        if ($patient->clinic_id !== $clinicId) {
            return response()->json(['success' => false, 'error' => 'Unauthorized'], 403);
        }

        $signaturePath = $patient->photo_consent_signature_path;
        if (!empty($validated['signature_image']) && $validated['consent_given']) {
            $raw = $validated['signature_image'];
            if (preg_match('/^data:image\/(png|jpeg|jpg);base64,/', $raw)) {
                $bin = base64_decode(preg_replace('/^data:image\/[^;]+;base64,/', '', $raw), true);
                if ($bin !== false) {
                    $sigFile = 'consent-signatures/' . $patient->id . '/' . time() . '_sig.png';
                    Storage::disk('local')->put($sigFile, $bin);
                    $signaturePath = $sigFile;
                    Log::info('PhotoVaultWebController: consent signature image stored', [
                        'patient_id' => $patient->id,
                        'path' => $sigFile,
                        'bytes' => strlen($bin),
                    ]);
                }
            } else {
                Log::warning('PhotoVaultWebController: signature_image did not match data URL pattern');
            }
        }

        $patient->update([
            'photo_consent_given' => $validated['consent_given'],
            'photo_consent_at' => $validated['consent_given'] ? now() : null,
            'photo_consent_signature_path' => $validated['consent_given'] ? $signaturePath : null,
        ]);

        Log::info('PhotoVaultWebController: Consent status updated', [
            'patient_id' => $patient->id,
            'consent_given' => $validated['consent_given'],
            'has_signature_file' => (bool) $signaturePath,
            'recorded_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => $validated['consent_given'] ? 'Photo consent recorded' : 'Photo consent revoked',
            'photo_consent_given' => $patient->photo_consent_given,
            'photo_consent_at' => $patient->photo_consent_at?->toIso8601String(),
            'photo_consent_signature_stored' => (bool) $patient->photo_consent_signature_path,
        ]);
    }

    /**
     * Check patient photo consent status
     */
    public function checkConsent(Patient $patient): JsonResponse
    {
        $clinicId = auth()->user()->clinic_id;

        if ($patient->clinic_id !== $clinicId) {
            return response()->json(['success' => false, 'error' => 'Unauthorized'], 403);
        }

        Log::info('PhotoVaultWebController: Consent check', [
            'patient_id' => $patient->id,
            'has_consent' => $patient->photo_consent_given,
        ]);

        return response()->json([
            'success' => true,
            'photo_consent_given' => (bool) $patient->photo_consent_given,
            'photo_consent_at' => $patient->photo_consent_at?->toIso8601String(),
        ]);
    }

    /**
     * Get before/after pairs for display
     */
    private function getBeforeAfterPairs(int $clinicId): \Illuminate\Support\Collection
    {
        return PatientPhoto::with('patient')
            ->where('clinic_id', $clinicId)
            ->where('photo_type', 'before')
            ->whereNotNull('pair_id')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(function ($before) {
                $after = PatientPhoto::find($before->pair_id);
                return [
                    'before' => $before,
                    'after' => $after,
                    'patient' => $before->patient,
                    'body_region' => $before->body_region,
                ];
            })
            ->filter(fn($pair) => $pair['after'] !== null);
    }
}
