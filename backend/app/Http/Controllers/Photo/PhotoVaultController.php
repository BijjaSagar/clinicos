<?php

namespace App\Http\Controllers\Photo;

use App\Http\Controllers\Controller;
use App\Models\PatientPhoto;
use App\Services\S3Service;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PhotoVaultController extends Controller
{
    protected S3Service $s3Service;

    public function __construct(S3Service $s3Service)
    {
        $this->s3Service = $s3Service;
    }

    /**
     * List photos
     */
    public function index(Request $request): JsonResponse
    {
        $clinicId = $request->user()->clinic_id;
        Log::info('Fetching photos', ['clinic_id' => $clinicId]);

        $query = PatientPhoto::where('clinic_id', $clinicId)
            ->withConsent();

        if ($request->patient_id) {
            $query->forPatient($request->patient_id);
        }

        if ($request->visit_id) {
            $query->forVisit($request->visit_id);
        }

        if ($request->body_region) {
            $query->forRegion($request->body_region);
        }

        if ($request->photo_type) {
            $query->byType($request->photo_type);
        }

        $photos = $query->orderBy('created_at', 'desc')->paginate(20);

        Log::info('Photos retrieved', ['count' => $photos->total()]);

        return response()->json($photos);
    }

    /**
     * Upload photo
     */
    public function upload(Request $request): JsonResponse
    {
        $clinicId = $request->user()->clinic_id;
        Log::info('Uploading photo', ['clinic_id' => $clinicId]);

        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'visit_id' => 'nullable|exists:visits,id',
            'photo' => 'required|image|max:10240', // Max 10MB
            'body_region' => 'nullable|string|max:100',
            'view_angle' => 'nullable|string|max:30',
            'condition_tag' => 'nullable|string|max:100',
            'procedure_tag' => 'nullable|string|max:100',
            'photo_type' => 'required|in:before,after,progress,clinical',
            'consent_obtained' => 'required|boolean',
        ]);

        if (!$validated['consent_obtained']) {
            Log::warning('Photo upload rejected - no consent', [
                'patient_id' => $validated['patient_id']
            ]);
            return response()->json([
                'message' => 'Patient consent is required for photo uploads',
            ], 400);
        }

        $file = $request->file('photo');
        $extension = $file->getClientOriginalExtension();
        $filename = sprintf(
            '%d/%d/%s_%s.%s',
            $clinicId,
            $validated['patient_id'],
            $validated['photo_type'],
            now()->format('Ymd_His'),
            $extension
        );

        // Upload to S3
        $s3Key = $this->s3Service->upload($file, $filename);

        Log::info('Photo uploaded to S3', ['s3_key' => $s3Key]);

        $photo = PatientPhoto::create([
            'clinic_id' => $clinicId,
            'patient_id' => $validated['patient_id'],
            'visit_id' => $validated['visit_id'] ?? null,
            's3_key' => $s3Key,
            's3_bucket' => config('filesystems.disks.s3.bucket'),
            'file_size_kb' => (int)($file->getSize() / 1024),
            'mime_type' => $file->getMimeType(),
            'body_region' => $validated['body_region'] ?? null,
            'view_angle' => $validated['view_angle'] ?? null,
            'condition_tag' => $validated['condition_tag'] ?? null,
            'procedure_tag' => $validated['procedure_tag'] ?? null,
            'photo_type' => $validated['photo_type'],
            'consent_obtained' => true,
            'consent_at' => now(),
            'is_encrypted' => true,
            'uploaded_by' => $request->user()->id,
        ]);

        Log::info('Photo record created', ['photo_id' => $photo->id]);

        return response()->json([
            'message' => 'Photo uploaded successfully',
            'photo' => $photo,
        ], 201);
    }

    /**
     * Show photo
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $clinicId = $request->user()->clinic_id;
        Log::info('Fetching photo', ['photo_id' => $id]);

        $photo = PatientPhoto::where('clinic_id', $clinicId)
            ->findOrFail($id);

        // Generate signed URL for secure access
        $signedUrl = $this->s3Service->getSignedUrl($photo->s3_key, 3600); // 1 hour

        return response()->json([
            'photo' => $photo,
            'url' => $signedUrl,
        ]);
    }

    /**
     * Delete photo
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $clinicId = $request->user()->clinic_id;
        Log::info('Deleting photo', ['photo_id' => $id]);

        $photo = PatientPhoto::where('clinic_id', $clinicId)
            ->findOrFail($id);

        // Soft delete (keep in S3 for audit)
        $photo->delete();

        Log::info('Photo deleted', ['photo_id' => $id]);

        return response()->json([
            'message' => 'Photo deleted successfully',
        ]);
    }

    /**
     * Compare photos (before/after)
     */
    public function compare(Request $request): JsonResponse
    {
        $clinicId = $request->user()->clinic_id;
        Log::info('Comparing photos', [
            'clinic_id' => $clinicId,
            'patient_id' => $request->patient_id,
            'region' => $request->region
        ]);

        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'region' => 'nullable|string|max:100',
            'visit_ids' => 'nullable|array',
            'visit_ids.*' => 'exists:visits,id',
        ]);

        $query = PatientPhoto::where('clinic_id', $clinicId)
            ->forPatient($validated['patient_id'])
            ->withConsent()
            ->with('visit');

        if (isset($validated['region'])) {
            $query->forRegion($validated['region']);
        }

        if (!empty($validated['visit_ids'])) {
            $query->whereIn('visit_id', $validated['visit_ids']);
        }

        $photos = $query->orderBy('created_at', 'asc')->get();

        // Generate signed URLs
        $photosWithUrls = $photos->map(function ($photo) {
            return [
                'photo' => $photo,
                'url' => $this->s3Service->getSignedUrl($photo->s3_key, 3600),
            ];
        });

        Log::info('Photos for comparison retrieved', ['count' => $photos->count()]);

        return response()->json([
            'photos' => $photosWithUrls,
        ]);
    }

    /**
     * Get photos by patient
     */
    public function byPatient(Request $request, int $patientId): JsonResponse
    {
        $clinicId = $request->user()->clinic_id;
        Log::info('Fetching photos by patient', ['patient_id' => $patientId]);

        $photos = PatientPhoto::where('clinic_id', $clinicId)
            ->forPatient($patientId)
            ->withConsent()
            ->orderBy('created_at', 'desc')
            ->get();

        // Group by body region
        $grouped = $photos->groupBy('body_region');

        Log::info('Patient photos retrieved', ['count' => $photos->count()]);

        return response()->json([
            'photos' => $photos,
            'by_region' => $grouped,
        ]);
    }
}
