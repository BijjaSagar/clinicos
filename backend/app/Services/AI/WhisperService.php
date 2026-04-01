<?php

namespace App\Services\AI;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * OpenAI Whisper transcription (used by API AI assistant).
 */
class WhisperService
{
    public function transcribe(UploadedFile $file, string $language = 'en'): string
    {
        $apiKey = config('services.openai.api_key');
        if (!$apiKey) {
            Log::warning('WhisperService: OPENAI_API_KEY not configured');
            return '';
        }

        Log::info('WhisperService: transcribe', [
            'language' => $language,
            'name' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
        ]);

        try {
            $payload = [
                'model' => 'whisper-1',
                'response_format' => 'json',
                'prompt' => 'Clinical consultation audio from Indian clinic. Accurately transcribe Hindi-English mixed speech and preserve medical terms.',
            ];
            if ($language !== 'auto') {
                $payload['language'] = $language;
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
            ])->attach(
                'file',
                file_get_contents($file->path()),
                $file->getClientOriginalName()
            )->post('https://api.openai.com/v1/audio/transcriptions', $payload);

            if ($response->successful()) {
                $text = (string) $response->json('text', '');
                Log::info('WhisperService: transcribe ok', ['length' => strlen($text)]);

                return $text;
            }

            Log::error('WhisperService: API error', ['body' => $response->body()]);

            return '';
        } catch (\Throwable $e) {
            Log::error('WhisperService: exception', ['error' => $e->getMessage()]);

            return '';
        }
    }
}
