<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected string $apiUrl;
    protected string $token;
    protected string $phoneNumberId;

    public function __construct()
    {
        $this->apiUrl = config('services.whatsapp.api_url', 'https://graph.facebook.com/v18.0');
        $this->token = config('services.whatsapp.token', '');
        $this->phoneNumberId = config('services.whatsapp.phone_number_id', '');
        
        Log::info('WhatsAppService initialized');
    }

    public function sendMessage(string $to, string $message): array
    {
        Log::info('WhatsAppService@sendMessage', ['to' => $to, 'message' => substr($message, 0, 50)]);

        // Format phone number (remove spaces, add country code if needed)
        $to = $this->formatPhoneNumber($to);

        if (empty($this->token) || empty($this->phoneNumberId)) {
            Log::warning('WhatsApp credentials not configured');
            return ['success' => false, 'error' => 'WhatsApp not configured'];
        }

        try {
            $response = Http::withToken($this->token)
                ->post("{$this->apiUrl}/{$this->phoneNumberId}/messages", [
                    'messaging_product' => 'whatsapp',
                    'to' => $to,
                    'type' => 'text',
                    'text' => [
                        'body' => $message,
                    ],
                ]);

            if ($response->successful()) {
                Log::info('WhatsApp message sent successfully', ['to' => $to]);
                return [
                    'success' => true,
                    'message_id' => $response->json('messages.0.id'),
                ];
            }

            Log::error('WhatsApp API error', ['response' => $response->json()]);
            return [
                'success' => false,
                'error' => $response->json('error.message', 'Unknown error'),
            ];
        } catch (\Exception $e) {
            Log::error('WhatsApp service exception', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function sendTemplate(string $to, string $templateName, array $parameters = []): array
    {
        Log::info('WhatsAppService@sendTemplate', ['to' => $to, 'template' => $templateName]);

        $to = $this->formatPhoneNumber($to);

        if (empty($this->token) || empty($this->phoneNumberId)) {
            return ['success' => false, 'error' => 'WhatsApp not configured'];
        }

        try {
            $payload = [
                'messaging_product' => 'whatsapp',
                'to' => $to,
                'type' => 'template',
                'template' => [
                    'name' => $templateName,
                    'language' => ['code' => 'en'],
                ],
            ];

            if (!empty($parameters)) {
                $payload['template']['components'] = [
                    [
                        'type' => 'body',
                        'parameters' => array_map(fn($p) => ['type' => 'text', 'text' => $p], $parameters),
                    ],
                ];
            }

            $response = Http::withToken($this->token)
                ->post("{$this->apiUrl}/{$this->phoneNumberId}/messages", $payload);

            return [
                'success' => $response->successful(),
                'message_id' => $response->json('messages.0.id'),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function sendDocument(string $to, string $documentUrl, string $caption = ''): array
    {
        Log::info('WhatsAppService@sendDocument', ['to' => $to, 'url' => $documentUrl]);

        $to = $this->formatPhoneNumber($to);

        if (empty($this->token) || empty($this->phoneNumberId)) {
            return ['success' => false, 'error' => 'WhatsApp not configured'];
        }

        try {
            $response = Http::withToken($this->token)
                ->post("{$this->apiUrl}/{$this->phoneNumberId}/messages", [
                    'messaging_product' => 'whatsapp',
                    'to' => $to,
                    'type' => 'document',
                    'document' => [
                        'link' => $documentUrl,
                        'caption' => $caption,
                    ],
                ]);

            return [
                'success' => $response->successful(),
                'message_id' => $response->json('messages.0.id'),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    protected function formatPhoneNumber(string $phone): string
    {
        // Remove all non-digit characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Add India country code if not present
        if (strlen($phone) === 10) {
            $phone = '91' . $phone;
        }
        
        return $phone;
    }
}
