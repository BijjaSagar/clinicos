<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Patient\CreatePatientRequest;
use App\Models\Patient;
use App\Models\PatientPhoto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PatientWebController extends Controller
{
    public function index(Request $request): View
    {
        $clinicId = auth()->user()->clinic_id;
        $search   = $request->input('search');
        $filter   = $request->input('filter'); // e.g. 'abha', 'active', etc.

        $query = Patient::where('clinic_id', $clinicId);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('abha_id', 'like', "%{$search}%")
                  ->orWhere('abha_address', 'like', "%{$search}%");
            });
        }

        if ($filter === 'abha') {
            $query->whereNotNull('abha_id');
        }

        $patients = $query->orderByDesc('updated_at')->paginate(20)->withQueryString();

        return view('patients.index', compact('patients', 'search', 'filter'));
    }

    public function create(): View
    {
        return view('patients.create');
    }

    public function store(CreatePatientRequest $request): RedirectResponse
    {
        try {
            $data              = $request->validated();
            $data['clinic_id'] = auth()->user()->clinic_id;

            $patient = Patient::create($data);

            Log::info('Patient created via web', ['patient_id' => $patient->id]);

            return redirect()
                ->route('patients.show', $patient)
                ->with('success', 'Patient created successfully.');
        } catch (\Throwable $e) {
            Log::error('Patient store error', ['error' => $e->getMessage()]);

            return back()
                ->withInput()
                ->with('error', 'Could not create patient. Please try again.');
        }
    }

    public function show(Patient $patient): View
    {
        $this->authorizeClinic($patient->clinic_id);

        $patient->load([
            'visits.prescriptions',
            'invoices',
            'photos',
        ]);

        return view('patients.show', compact('patient'));
    }

    public function edit(Patient $patient): View
    {
        $this->authorizeClinic($patient->clinic_id);

        return view('patients.edit', compact('patient'));
    }

    public function update(Request $request, Patient $patient): RedirectResponse
    {
        $this->authorizeClinic($patient->clinic_id);

        $validated = $request->validate([
            'name'                => ['sometimes', 'required', 'string', 'max:255'],
            'dob'                 => ['nullable', 'date'],
            'age_years'           => ['nullable', 'integer', 'min:0', 'max:150'],
            'sex'                 => ['nullable', 'in:male,female,other'],
            'blood_group'         => ['nullable', 'string', 'max:10'],
            'phone'               => ['sometimes', 'required', 'string', 'max:15'],
            'phone_alt'           => ['nullable', 'string', 'max:15'],
            'email'               => ['nullable', 'email', 'max:255'],
            'address'             => ['nullable', 'string', 'max:500'],
            'known_allergies'     => ['nullable', 'string'],
            'chronic_conditions'  => ['nullable', 'string'],
            'current_medications' => ['nullable', 'string'],
            'family_history'      => ['nullable', 'string'],
            'referred_by'         => ['nullable', 'string', 'max:255'],
            'next_followup_date'  => ['nullable', 'date'],
        ]);

        try {
            $patient->update($validated);

            Log::info('Patient updated via web', ['patient_id' => $patient->id]);

            return back()->with('success', 'Patient updated successfully.');
        } catch (\Throwable $e) {
            Log::error('Patient update error', ['patient_id' => $patient->id, 'error' => $e->getMessage()]);

            return back()
                ->withInput()
                ->with('error', 'Could not update patient. Please try again.');
        }
    }

    public function destroy(Patient $patient): RedirectResponse
    {
        $this->authorizeClinic($patient->clinic_id);

        try {
            $patient->delete(); // soft delete via deleted_at

            Log::info('Patient soft-deleted via web', ['patient_id' => $patient->id]);

            return redirect()
                ->route('patients.index')
                ->with('success', 'Patient record deleted successfully.');
        } catch (\Throwable $e) {
            Log::error('Patient delete error', ['patient_id' => $patient->id, 'error' => $e->getMessage()]);

            return back()->with('error', 'Could not delete patient. Please try again.');
        }
    }

    public function uploadPhoto(Request $request, Patient $patient): RedirectResponse
    {
        $this->authorizeClinic($patient->clinic_id);

        Log::info('PatientWebController@uploadPhoto', ['patient_id' => $patient->id]);

        $validated = $request->validate([
            'photo' => ['required', 'image', 'max:10240'], // Max 10MB
            'photo_type' => ['required', 'in:before,after,progress,clinical'],
            'body_region' => ['nullable', 'string', 'max:100'],
            'condition_tag' => ['nullable', 'string', 'max:255'],
            'consent_obtained' => ['required', 'accepted'],
        ], [
            'photo.required' => 'Please select a photo to upload.',
            'photo.image' => 'The file must be an image (JPEG, PNG, GIF, etc.).',
            'photo.max' => 'The photo must not exceed 10MB in size.',
            'photo_type.required' => 'Please select a photo type (Before, After, Progress, or Clinical).',
            'photo_type.in' => 'Invalid photo type selected.',
            'consent_obtained.required' => 'Patient consent is required to upload clinical photos.',
            'consent_obtained.accepted' => 'You must confirm that patient consent has been obtained.',
        ]);

        try {
            $file = $request->file('photo');
            $clinicId = auth()->user()->clinic_id;
            
            // Generate unique filename
            $filename = sprintf(
                'patient_photos/clinic_%d/patient_%d/%s_%s.%s',
                $clinicId,
                $patient->id,
                $validated['photo_type'],
                now()->format('Ymd_His'),
                $file->getClientOriginalExtension()
            );

            // Store file in public disk (accessible via /storage URL)
            $path = $file->storeAs('', $filename, 'public');
            
            Log::info('Photo stored locally', ['path' => $path]);

            // Create database record
            $photo = PatientPhoto::create([
                'clinic_id' => $clinicId,
                'patient_id' => $patient->id,
                'visit_id' => null,
                's3_key' => $path,
                's3_bucket' => 'local', // Using local storage
                'file_size_kb' => round($file->getSize() / 1024),
                'mime_type' => $file->getMimeType(),
                'body_region' => $validated['body_region'],
                'condition_tag' => $validated['condition_tag'],
                'photo_type' => $validated['photo_type'],
                'consent_obtained' => true,
                'consent_at' => now(),
                'is_encrypted' => false,
                'can_use_for_marketing' => false,
                'uploaded_by' => auth()->id(),
            ]);

            Log::info('Patient photo record created', ['photo_id' => $photo->id, 'patient_id' => $patient->id]);

            return back()->with('success', 'Photo uploaded successfully.');
            
        } catch (\Throwable $e) {
            Log::error('Photo upload error', [
                'patient_id' => $patient->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->with('error', 'Could not upload photo: ' . $e->getMessage());
        }
    }

    public function viewPhoto(Patient $patient, PatientPhoto $photo)
    {
        $this->authorizeClinic($patient->clinic_id);
        
        // Verify photo belongs to this patient
        abort_unless($photo->patient_id === $patient->id, 404);

        Log::info('Viewing patient photo', [
            'photo_id' => $photo->id,
            'patient_id' => $patient->id,
            's3_key' => $photo->s3_key,
            'file_path' => $photo->file_path ?? null,
            'is_encrypted' => $photo->is_encrypted,
            'storage_disk' => $photo->storage_disk ?? null,
        ]);

        $rel = $photo->file_path ?: $photo->s3_key;

        if ($photo->is_encrypted && ($photo->storage_disk ?? '') === 'local' && $rel) {
            Log::info('PatientWebController: decrypting photo from local disk', ['rel' => $rel]);
            try {
                $cipher = Storage::disk('local')->get($rel);
                $raw = Crypt::decryptString($cipher);

                return response($raw, 200, [
                    'Content-Type' => $photo->mime_type ?? 'image/jpeg',
                    'Cache-Control' => 'private, max-age=3600',
                ]);
            } catch (\Throwable $e) {
                Log::error('PatientWebController: decrypt failed', ['photo_id' => $photo->id, 'error' => $e->getMessage()]);
                abort(404, 'Photo not available');
            }
        }

        // The file is stored in storage/app/public/{path}
        $storagePath = storage_path('app/public/' . $rel);

        Log::info('Looking for photo', ['storage_path' => $storagePath, 'exists' => file_exists($storagePath)]);

        if (file_exists($storagePath)) {
            return response()->file($storagePath, [
                'Content-Type' => $photo->mime_type ?? 'image/jpeg',
                'Cache-Control' => 'private, max-age=3600',
            ]);
        }

        $altPaths = [
            base_path('storage/app/public/' . $rel),
            $rel ? Storage::disk('public')->path($rel) : null,
        ];

        foreach (array_filter($altPaths) as $path) {
            if (file_exists($path)) {
                Log::info('Found photo at alternate path', ['path' => $path]);

                return response()->file($path, [
                    'Content-Type' => $photo->mime_type ?? 'image/jpeg',
                    'Cache-Control' => 'private, max-age=3600',
                ]);
            }
        }

        Log::error('Photo file not found', ['photo_id' => $photo->id, 'primary_path' => $storagePath]);
        abort(404, 'Photo not found');
    }

    public function deletePhoto(Patient $patient, PatientPhoto $photo): RedirectResponse
    {
        $this->authorizeClinic($patient->clinic_id);
        
        // Verify photo belongs to this patient
        abort_unless($photo->patient_id === $patient->id, 404);

        Log::info('Deleting patient photo', ['photo_id' => $photo->id, 'patient_id' => $patient->id]);

        try {
            $rel = $photo->file_path ?: $photo->s3_key;
            if ($photo->is_encrypted && ($photo->storage_disk ?? '') === 'local' && $rel) {
                Storage::disk('local')->delete($rel);
                Log::info('Encrypted photo file deleted from local disk', ['path' => $rel]);
            } else {
                $storagePath = storage_path('app/public/' . $rel);
                if (file_exists($storagePath)) {
                    unlink($storagePath);
                    Log::info('Photo file deleted', ['path' => $storagePath]);
                } elseif ($rel && Storage::disk('public')->exists($rel)) {
                    Storage::disk('public')->delete($rel);
                    Log::info('Photo file deleted via Storage public', ['path' => $rel]);
                }
            }

            $photo->delete();

            return back()->with('success', 'Photo deleted successfully.');
            
        } catch (\Throwable $e) {
            Log::error('Photo delete error', [
                'photo_id' => $photo->id,
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'Could not delete photo: ' . $e->getMessage());
        }
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function authorizeClinic(int $resourceClinicId): void
    {
        abort_unless(auth()->user()->clinic_id === $resourceClinicId, 403);
    }
}
