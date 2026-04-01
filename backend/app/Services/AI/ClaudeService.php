<?php

namespace App\Services\AI;

use App\Models\Visit;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Anthropic Claude — structured EMR extraction, summaries, Rx hints (API AI assistant).
 */
class ClaudeService
{
    private function apiKey(): ?string
    {
        $k = config('services.anthropic.api_key');

        return $k !== '' ? $k : null;
    }

    private function messages(string $system, string $user, int $maxTokens = 2048): string
    {
        $key = $this->apiKey();
        if (!$key) {
            Log::warning('ClaudeService: ANTHROPIC_API_KEY not configured');

            return '';
        }

        Log::info('ClaudeService: messages request', [
            'system_len' => strlen($system),
            'user_len' => strlen($user),
            'max_tokens' => $maxTokens,
        ]);

        try {
            $response = Http::withHeaders([
                'x-api-key' => $key,
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ])->post('https://api.anthropic.com/v1/messages', [
                'model' => 'claude-3-5-haiku-20241022',
                'max_tokens' => $maxTokens,
                'system' => $system,
                'messages' => [
                    ['role' => 'user', 'content' => $user],
                ],
            ]);

            if ($response->successful()) {
                $text = '';
                foreach ($response->json('content', []) as $block) {
                    if (($block['type'] ?? '') === 'text') {
                        $text .= $block['text'] ?? '';
                    }
                }
                Log::info('ClaudeService: messages ok', ['out_len' => strlen($text)]);

                return trim($text);
            }

            Log::error('ClaudeService: API error', ['body' => $response->body()]);

            return '';
        } catch (\Throwable $e) {
            Log::error('ClaudeService: exception', ['error' => $e->getMessage()]);

            return '';
        }
    }

    public function extractStructuredFields(string $systemPrompt, string $userText): array
    {
        $user = "Transcript:\n{$userText}\n\nReply with ONLY valid JSON object, no markdown.";
        $raw = $this->messages($systemPrompt, $user, 4096);
        if ($raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        if (preg_match('/\{[\s\S]*\}/', $raw, $m)) {
            $decoded = json_decode($m[0], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        Log::warning('ClaudeService: extractStructuredFields could not parse JSON', ['raw_len' => strlen($raw)]);

        return [];
    }

    public function generateConsultationSummary(Visit $visit, string $language = 'en'): string
    {
        $ctx = [
            'patient' => $visit->patient?->name,
            'chief_complaint' => $visit->chief_complaint,
            'diagnosis' => $visit->diagnosis_text,
            'plan' => $visit->plan,
        ];
        $system = 'You are a clinical assistant. Write a short, patient-friendly summary of the visit in plain language. No JSON.';
        $user = 'Language: ' . $language . "\nContext:\n" . json_encode($ctx, JSON_UNESCAPED_UNICODE);

        $out = $this->messages($system, $user, 1024);

        return $out !== '' ? $out : 'Summary could not be generated. Configure ANTHROPIC_API_KEY.';
    }

    public function suggestPrescription(
        string $diagnosis,
        string $specialty,
        ?int $patientAge,
        ?string $patientSex,
        array $allergies,
        array $currentMeds,
    ): array {
        $system = 'You are a clinical decision support assistant for Indian outpatient practice. '
            . 'Suggest a conservative prescription draft for the doctor to review. '
            . 'Reply with ONLY valid JSON: { "drugs": [ { "name": "", "dose": "", "duration": "", "notes": "" } ], "warnings": [] }';
        $user = json_encode([
            'diagnosis' => $diagnosis,
            'specialty' => $specialty,
            'patient_age' => $patientAge,
            'patient_sex' => $patientSex,
            'allergies' => $allergies,
            'current_meds' => $currentMeds,
        ], JSON_UNESCAPED_UNICODE);

        $raw = $this->messages($system, $user, 4096);
        if ($raw === '') {
            return ['drugs' => [], 'warnings' => ['AI not configured']];
        }

        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        return ['drugs' => [], 'warnings' => ['Could not parse AI response'], 'raw' => $raw];
    }
}
