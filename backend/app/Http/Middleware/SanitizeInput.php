<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SanitizeInput
{
    /**
     * Fields that should never be sanitized (passwords, tokens, etc.)
     */
    protected array $except = [
        'password',
        'password_confirmation',
        'current_password',
        'access_token',
        'app_secret',
        '_token',
        'token',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $input = $request->all();
        $sanitized = $this->sanitize($input);
        $request->merge($sanitized);

        return $next($request);
    }

    private function sanitize(array $data): array
    {
        foreach ($data as $key => $value) {
            if (in_array($key, $this->except, true)) {
                continue;
            }

            if (is_string($value)) {
                // Strip null bytes
                $value = str_replace("\0", '', $value);
                // Strip HTML tags from non-content fields
                if (!$this->isContentField($key)) {
                    $value = strip_tags($value);
                }
                // Trim whitespace
                $value = trim($value);
                $data[$key] = $value;
            } elseif (is_array($value)) {
                $data[$key] = $this->sanitize($value);
            }
        }

        return $data;
    }

    /**
     * Fields that may contain HTML/rich content (e.g., WYSIWYG editors)
     */
    private function isContentField(string $key): bool
    {
        return in_array($key, [
            'content',
            'body',
            'notes',
            'description',
            'discharge_notes',
            'clinical_notes',
            'progress_note',
            'discharge_summary_footer',
        ], true);
    }
}
