<?php

namespace App\Http\Controllers\AI;

use App\Http\Controllers\Controller;
use App\Models\Visit;
use App\Services\AI\WhisperService;
use App\Services\AI\ClaudeService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AiAssistantController extends Controller
{
    public function __construct(
        private readonly WhisperService $whisper,
        private readonly ClaudeService  $claude,
    ) {}

    /**
     * POST /ai/transcribe
     * Accepts audio file, returns raw transcript via Whisper STT.
     */
    public function transcribe(Request $request): JsonResponse
    {
        $request->validate([
            'audio'    => 'required|file|mimes:webm,mp4,m4a,wav,mp3|max:25600',
            'language' => 'nullable|string|in:en,hi,mr',
        ]);

        $transcript = $this->whisper->transcribe(
            file:     $request->file('audio'),
            language: $request->input('language', 'en'),
        );

        return response()->json(['transcript' => $transcript]);
    }

    /**
     * POST /ai/map-fields
     * Takes raw transcript + specialty context, returns structured EMR field values.
     *
     * This is the core AI feature: "Patient has a 2cm raised red plaque on the left cheek"
     * → { lesion_type: "plaque", size_cm: 2.0, location: "left cheek", colour: "red" }
     */
    public function mapToEmrFields(Request $request): JsonResponse
    {
        $request->validate([
            'transcript' => 'required|string|max:5000',
            'specialty'  => 'required|string',
            'template'   => 'nullable|array', // field schema to guide mapping
        ]);

        $systemPrompt = $this->buildSystemPrompt(
            $request->input('specialty'),
            $request->input('template', [])
        );

        $mappedFields = $this->claude->extractStructuredFields(
            systemPrompt: $systemPrompt,
            userText:     $request->input('transcript'),
        );

        return response()->json([
            'fields'     => $mappedFields,
            'transcript' => $request->input('transcript'),
        ]);
    }

    /**
     * POST /ai/summarise/{visitId}
     * Generates a patient-friendly consultation summary from a finalised EMR.
     */
    public function summarise(int $visitId): JsonResponse
    {
        $visit = Visit::with(['patient', 'prescriptions.drugs', 'procedures', 'scales'])
            ->forClinic(auth()->user()->clinic_id)
            ->findOrFail($visitId);

        $summary = $this->claude->generateConsultationSummary(
            visit:    $visit,
            language: 'en', // TODO: support Hindi
        );

        // Save summary to visit
        $visit->ai_summary = $summary;
        $visit->save();

        return response()->json([
            'summary'  => $summary,
            'visit_id' => $visitId,
        ]);
    }

    /**
     * POST /ai/suggest-rx
     * Given a diagnosis, suggest a standard prescription template for the doctor to review.
     */
    public function suggestRx(Request $request): JsonResponse
    {
        $request->validate([
            'diagnosis'    => 'required|string',
            'specialty'    => 'required|string',
            'patient_age'  => 'nullable|integer',
            'patient_sex'  => 'nullable|string|in:M,F,O',
            'allergies'    => 'nullable|array',
            'current_meds' => 'nullable|array',
        ]);

        $suggestion = $this->claude->suggestPrescription(
            diagnosis:   $request->input('diagnosis'),
            specialty:   $request->input('specialty'),
            patientAge:  $request->input('patient_age'),
            patientSex:  $request->input('patient_sex'),
            allergies:   $request->input('allergies', []),
            currentMeds: $request->input('current_meds', []),
        );

        return response()->json(['suggestion' => $suggestion]);
    }

    // ── Private helpers ────────────────────────────────────────────────────

    private function buildSystemPrompt(string $specialty, array $templateFields): string
    {
        $fieldList = collect($templateFields)
            ->map(fn($f) => "- {$f['key']} ({$f['type']}): {$f['description']}")
            ->implode("\n");

        return <<<PROMPT
        You are a medical AI assistant embedded in ClinicOS, a specialty clinic EMR system in India.
        Your role is to extract structured clinical data from a doctor's dictation and map it to the correct EMR fields.

        Current specialty: {$specialty}

        Available EMR fields:
        {$fieldList}

        Instructions:
        - Extract only values explicitly stated in the transcript
        - Return a flat JSON object where keys match the EMR field names exactly
        - Use medical units as specified (cm for size, degrees for ROM, 0-10 for VAS)
        - If a value is ambiguous, include it with a "needs_confirmation: true" flag
        - Do not invent or infer values not stated in the transcript
        - Understand Indian medical context: "VAS 7" means pain 7/10, "CDR 0.6" is cup-to-disc ratio, etc.
        PROMPT;
    }
}
