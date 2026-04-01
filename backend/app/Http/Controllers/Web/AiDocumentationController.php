<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Visit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * AI Documentation Assistant Controller
 * Handles voice-to-EMR transcription and AI-powered clinical note generation
 */
class AiDocumentationController extends Controller
{
    /**
     * Show the AI assistant interface
     */
    public function index(): View
    {
        Log::info('AiDocumentationController: Loading AI assistant page');
        return view('ai-assistant.index');
    }

    /**
     * Transcribe audio to text using Whisper API
     */
    public function transcribe(Request $request): JsonResponse
    {
        Log::info('AiDocumentationController: Transcribing audio');

        $request->validate([
            'audio' => 'required|file|mimes:webm,mp3,wav,m4a,mp4,mpeg,mpga,oga,ogg',
            'language' => 'nullable|string|in:en,hi,mr,ta,te,kn,ml,bn,gu,pa,auto',
        ]);

        $apiKey = config('services.openai.api_key');
        
        if (!$apiKey) {
            Log::warning('AiDocumentationController: OpenAI API key not configured');
            return response()->json([
                'success' => false,
                'error' => 'AI service not configured. Please set OPENAI_API_KEY.',
            ], 503);
        }

        try {
            $audio = $request->file('audio');
            $language = $request->input('language', 'auto');

            $payload = [
                'model' => 'whisper-1',
                'response_format' => 'json',
                'prompt' => 'Clinical consultation audio from Indian clinic. Accurately transcribe Hindi-English mixed speech and preserve medical terms, drug names, and vitals.',
            ];
            if ($language !== 'auto') {
                $payload['language'] = $language;
            }

            Log::info('AiDocumentationController: Whisper request payload prepared', [
                'language' => $language,
                'audio_name' => $audio->getClientOriginalName(),
                'audio_size' => $audio->getSize(),
            ]);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
            ])->attach(
                'file', file_get_contents($audio->path()), $audio->getClientOriginalName()
            )->post('https://api.openai.com/v1/audio/transcriptions', $payload);

            if ($response->successful()) {
                $transcription = $response->json('text');
                Log::info('AiDocumentationController: Transcription successful', ['length' => strlen($transcription)]);
                
                return response()->json([
                    'success' => true,
                    'transcription' => $transcription,
                ]);
            } else {
                Log::error('AiDocumentationController: Whisper API error', ['response' => $response->body()]);
                return response()->json([
                    'success' => false,
                    'error' => 'Transcription failed. Please try again.',
                ], 500);
            }
        } catch (\Throwable $e) {
            Log::error('AiDocumentationController: Transcription error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'error' => 'Transcription error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate structured EMR notes from transcription
     */
    public function generateNotes(Request $request): JsonResponse
    {
        Log::info('AiDocumentationController: Generating clinical notes');

        $validated = $request->validate([
            'transcription' => 'required|string|max:10000',
            'specialty' => 'nullable|string',
            'note_type' => 'nullable|string|in:soap,progress,initial,discharge',
            'visit_id' => 'nullable|exists:visits,id',
            'language_context' => 'nullable|string|in:en,hi,mixed',
        ]);

        $apiKey = config('services.openai.api_key');
        
        if (!$apiKey) {
            return response()->json([
                'success' => false,
                'error' => 'AI service not configured',
            ], 503);
        }

        $specialty = $validated['specialty'] ?? 'general';
        $noteType = $validated['note_type'] ?? 'soap';
        $languageContext = $validated['language_context'] ?? 'mixed';

        $systemPrompt = $this->buildSystemPrompt($specialty, $noteType, $languageContext);

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4o',
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => "Convert this clinical conversation to structured EMR notes:\n\n" . $validated['transcription']],
                ],
                'temperature' => 0.3,
                'max_tokens' => 2000,
            ]);

            if ($response->successful()) {
                $content = $response->json('choices.0.message.content');
                $structuredNotes = $this->parseNotes($content, $noteType);
                $structuredNotes['consultation_summary'] = $this->buildConsultationSummary($structuredNotes);
                
                Log::info('AiDocumentationController: Notes generated', [
                    'note_type' => $noteType,
                    'language_context' => $languageContext,
                    'summary_length' => strlen($structuredNotes['consultation_summary'] ?? ''),
                ]);

                return response()->json([
                    'success' => true,
                    'notes' => $structuredNotes,
                    'raw_content' => $content,
                ]);
            } else {
                Log::error('AiDocumentationController: GPT API error', ['response' => $response->body()]);
                return response()->json([
                    'success' => false,
                    'error' => 'Note generation failed',
                ], 500);
            }
        } catch (\Throwable $e) {
            Log::error('AiDocumentationController: Note generation error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Extract ICD-10 codes from clinical notes
     */
    public function extractCodes(Request $request): JsonResponse
    {
        Log::info('AiDocumentationController: Extracting ICD codes');

        $validated = $request->validate([
            'notes' => 'required|string|max:5000',
            'specialty' => 'nullable|string',
        ]);

        $apiKey = config('services.openai.api_key');
        
        if (!$apiKey) {
            return response()->json(['success' => false, 'error' => 'AI service not configured'], 503);
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4o-mini',
                'messages' => [
                    ['role' => 'system', 'content' => "You are a medical coding specialist. Extract relevant ICD-10-CM codes from clinical notes. Return ONLY a JSON array with objects containing 'code' and 'description' fields. Focus on primary diagnoses and relevant secondary conditions."],
                    ['role' => 'user', 'content' => $validated['notes']],
                ],
                'temperature' => 0.1,
                'max_tokens' => 500,
            ]);

            if ($response->successful()) {
                $content = $response->json('choices.0.message.content');
                $codes = json_decode($content, true) ?? [];
                
                Log::info('AiDocumentationController: ICD codes extracted', ['count' => count($codes)]);

                return response()->json([
                    'success' => true,
                    'codes' => $codes,
                ]);
            }

            return response()->json(['success' => false, 'error' => 'Code extraction failed'], 500);
        } catch (\Throwable $e) {
            Log::error('AiDocumentationController: Code extraction error', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Save AI-generated notes to a visit
     */
    public function saveToVisit(Request $request, Visit $visit): JsonResponse
    {
        Log::info('AiDocumentationController: Saving notes to visit', ['visit_id' => $visit->id]);

        abort_unless(auth()->user()->clinic_id === $visit->clinic_id, 403);

        $validated = $request->validate([
            'chief_complaint' => 'nullable|string|max:500',
            'history' => 'nullable|string|max:5000',
            'examination' => 'nullable|string|max:5000',
            'assessment' => 'nullable|string|max:2000',
            'plan' => 'nullable|string|max:2000',
            'icd_codes' => 'nullable|array',
        ]);

        try {
            $updateData = [];
            
            if (!empty($validated['chief_complaint'])) {
                $updateData['chief_complaint'] = $validated['chief_complaint'];
            }
            if (!empty($validated['history'])) {
                $updateData['history_of_present_illness'] = $validated['history'];
            }
            if (!empty($validated['examination'])) {
                $updateData['physical_examination'] = $validated['examination'];
            }
            if (!empty($validated['assessment'])) {
                $updateData['assessment'] = $validated['assessment'];
            }
            if (!empty($validated['plan'])) {
                $updateData['plan'] = $validated['plan'];
            }
            if (!empty($validated['icd_codes'])) {
                $updateData['icd_codes'] = json_encode($validated['icd_codes']);
            }

            $visit->update($updateData);

            Log::info('AiDocumentationController: Notes saved to visit', ['visit_id' => $visit->id]);

            return response()->json([
                'success' => true,
                'message' => 'Notes saved to patient record',
            ]);
        } catch (\Throwable $e) {
            Log::error('AiDocumentationController: Save error', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Generate clinical letter (referral, discharge summary)
     */
    public function generateLetter(Request $request): JsonResponse
    {
        Log::info('AiDocumentationController: Generating clinical letter');

        $validated = $request->validate([
            'letter_type' => 'required|string|in:referral,discharge,follow_up,medical_certificate',
            'patient_info' => 'required|array',
            'clinical_info' => 'required|string|max:5000',
            'recipient' => 'nullable|string',
        ]);

        $apiKey = config('services.openai.api_key');
        
        if (!$apiKey) {
            return response()->json(['success' => false, 'error' => 'AI service not configured'], 503);
        }

        $letterPrompts = [
            'referral' => "Generate a professional medical referral letter.",
            'discharge' => "Generate a comprehensive discharge summary.",
            'follow_up' => "Generate a follow-up appointment letter.",
            'medical_certificate' => "Generate a medical certificate/fitness certificate.",
        ];

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4o-mini',
                'messages' => [
                    ['role' => 'system', 'content' => "You are a medical documentation assistant. " . $letterPrompts[$validated['letter_type']] . " Use proper medical terminology and formal language. Format as a proper letter."],
                    ['role' => 'user', 'content' => "Patient: " . json_encode($validated['patient_info']) . "\n\nClinical Information:\n" . $validated['clinical_info'] . ($validated['recipient'] ? "\n\nRecipient: " . $validated['recipient'] : '')],
                ],
                'temperature' => 0.3,
                'max_tokens' => 1500,
            ]);

            if ($response->successful()) {
                $letter = $response->json('choices.0.message.content');
                
                return response()->json([
                    'success' => true,
                    'letter' => $letter,
                ]);
            }

            return response()->json(['success' => false, 'error' => 'Letter generation failed'], 500);
        } catch (\Throwable $e) {
            Log::error('AiDocumentationController: Letter generation error', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Build system prompt based on specialty and note type
     */
    private function buildSystemPrompt(string $specialty, string $noteType, string $languageContext = 'mixed'): string
    {
        $specialtyContext = [
            'dermatology' => 'Focus on skin conditions, lesion descriptions (size, shape, color, distribution), dermatoscopy findings, and topical/systemic treatments.',
            'ophthalmology' => 'Include visual acuity, IOP, slit lamp findings, fundus examination, and refraction details.',
            'orthopaedics' => 'Include range of motion, muscle power grading, special tests, and imaging findings.',
            'ent' => 'Include ear/nose/throat examination findings, hearing tests, and endoscopy findings.',
            'gynaecology' => 'Include menstrual history, obstetric history, per vaginal examination, and USG findings.',
            'dental' => 'Include tooth charting, periodontal status, and treatment procedures.',
            'physiotherapy' => 'Include ROM measurements, muscle strength, functional assessments, and exercise prescriptions.',
            'general' => 'Include relevant systems review and general physical examination.',
        ];

        $noteStructures = [
            'soap' => 'Use SOAP format: S (Subjective - patient complaints, history), O (Objective - examination findings, vitals, investigations), A (Assessment - diagnoses), P (Plan - treatment, follow-up).',
            'progress' => 'Use Progress Note format: Current status, changes since last visit, response to treatment, ongoing issues, updated plan.',
            'initial' => 'Use Initial Evaluation format: Chief complaint, detailed HPI, PMH, medications, allergies, family/social history, ROS, examination, assessment, plan.',
            'discharge' => 'Use Discharge Summary format: Admission diagnosis, hospital course, procedures, discharge diagnosis, discharge medications, follow-up instructions.',
        ];

        $specialtyText = $specialtyContext[$specialty] ?? $specialtyContext['general'];
        $noteStructureText = $noteStructures[$noteType] ?? $noteStructures['soap'];
        
        $languageGuidance = match ($languageContext ?? 'mixed') {
            'hi' => 'Conversation may include Hindi clinical terms. Normalize to professional English EMR while retaining original clinical meaning.',
            'en' => 'Conversation is primarily English clinical dictation.',
            default => 'Conversation may be code-mixed Hindi-English. Preserve intent accurately and normalize output to professional English EMR.',
        };

        $prompt = "You are a medical documentation assistant for {$specialty}. {$specialtyText}\n\n";
        $prompt .= "{$noteStructureText}\n\n";
        $prompt .= "Guidelines:\n";
        $prompt .= "- {$languageGuidance}\n";
        $prompt .= "- Use proper medical terminology\n";
        $prompt .= "- Be concise but comprehensive\n";
        $prompt .= "- Include relevant negative findings\n";
        $prompt .= "- Extract all mentioned medications with dosages\n";
        $prompt .= "- Note any red flags or urgent findings\n";
        $prompt .= "- Output in a structured, parseable format\n";
        $prompt .= "- Use Indian medical conventions (units, medications)";

        return $prompt;
    }

    private function buildConsultationSummary(array $notes): string
    {
        $parts = [];

        if (!empty($notes['chief_complaint'])) {
            $parts[] = 'Chief complaint: ' . $notes['chief_complaint'];
        }
        if (!empty($notes['assessment'])) {
            $parts[] = 'Assessment: ' . $notes['assessment'];
        }
        if (!empty($notes['plan'])) {
            $parts[] = 'Plan: ' . $notes['plan'];
        }

        $summary = implode(' ', $parts);
        if (!$summary && !empty($notes['history'])) {
            $summary = mb_substr((string) $notes['history'], 0, 280);
        }

        return trim($summary);
    }

    /**
     * Parse AI-generated notes into structured format
     */
    private function parseNotes(string $content, string $noteType): array
    {
        $notes = [
            'chief_complaint' => '',
            'history' => '',
            'examination' => '',
            'assessment' => '',
            'plan' => '',
            'vitals' => [],
            'medications' => [],
            'investigations' => [],
        ];

        if ($noteType === 'soap') {
            if (preg_match('/S(?:ubjective)?[:\s]*(.+?)(?=O(?:bjective)?[:\s]|$)/is', $content, $matches)) {
                $subjective = trim($matches[1]);
                if (preg_match('/Chief\s*Complaint[:\s]*(.+?)(?=History|HPI|$)/is', $subjective, $cc)) {
                    $notes['chief_complaint'] = trim($cc[1]);
                }
                $notes['history'] = $subjective;
            }
            
            if (preg_match('/O(?:bjective)?[:\s]*(.+?)(?=A(?:ssessment)?[:\s]|$)/is', $content, $matches)) {
                $notes['examination'] = trim($matches[1]);
            }
            
            if (preg_match('/A(?:ssessment)?[:\s]*(.+?)(?=P(?:lan)?[:\s]|$)/is', $content, $matches)) {
                $notes['assessment'] = trim($matches[1]);
            }
            
            if (preg_match('/P(?:lan)?[:\s]*(.+?)$/is', $content, $matches)) {
                $notes['plan'] = trim($matches[1]);
            }
        } else {
            $notes['history'] = $content;
        }

        if (preg_match_all('/(?:BP|Blood Pressure)[:\s]*(\d{2,3}\/\d{2,3})/i', $content, $matches)) {
            $notes['vitals']['bp'] = $matches[1][0] ?? null;
        }
        if (preg_match_all('/(?:HR|Pulse|Heart Rate)[:\s]*(\d{2,3})/i', $content, $matches)) {
            $notes['vitals']['pulse'] = $matches[1][0] ?? null;
        }
        if (preg_match_all('/(?:Temp|Temperature)[:\s]*([\d.]+)/i', $content, $matches)) {
            $notes['vitals']['temperature'] = $matches[1][0] ?? null;
        }
        if (preg_match_all('/(?:SpO2|Oxygen|O2)[:\s]*(\d{2,3})/i', $content, $matches)) {
            $notes['vitals']['spo2'] = $matches[1][0] ?? null;
        }

        return $notes;
    }
}
