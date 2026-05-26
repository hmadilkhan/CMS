<?php

namespace App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class OpenAiService
{
    public function createResponse(string $message, ?string $previousResponseId = null): array
    {
        $apiKey = config('services.openai.api_key');

        if (blank($apiKey)) {
            throw new RuntimeException('OpenAI API key is not configured.');
        }

        $payload = array_filter([
            'model' => config('services.openai.model', 'gpt-4.1-mini'),
            'instructions' => 'You are a helpful CRM assistant. Answer clearly and professionally. Do not query or infer from the CRM database yet.',
            'input' => $message,
            'previous_response_id' => $previousResponseId,
            'max_output_tokens' => (int) config('services.openai.max_output_tokens', 1200),
        ], fn ($value) => ! is_null($value));

        $response = Http::withToken($apiKey)
            ->acceptJson()
            ->asJson()
            ->timeout((int) config('services.openai.timeout', 60))
            ->post('https://api.openai.com/v1/responses', $payload);

        if ($response->failed()) {
            throw new RuntimeException($response->json('error.message') ?? 'OpenAI request failed.');
        }

        $data = $response->json();

        return [
            'id' => Arr::get($data, 'id'),
            'model' => Arr::get($data, 'model', $payload['model']),
            'text' => $this->extractText($data),
            'usage' => Arr::get($data, 'usage', []),
            'payload' => $payload,
            'raw' => $data,
        ];
    }

    public function createJsonResponse(string $instructions, array|string $input, int $maxOutputTokens = 1200, ?array $jsonSchema = null): array
    {
        $apiKey = config('services.openai.api_key');

        if (blank($apiKey)) {
            throw new RuntimeException('OpenAI API key is not configured.');
        }

        $payload = [
            'model' => config('services.openai.model', 'gpt-4.1-mini'),
            'instructions' => $instructions,
            'input' => is_array($input) ? json_encode($input, JSON_PRETTY_PRINT) : $input,
            'text' => [
                'format' => $jsonSchema ?? [
                    'type' => 'json_object',
                ],
            ],
            'max_output_tokens' => $maxOutputTokens,
        ];

        $response = Http::withToken($apiKey)
            ->acceptJson()
            ->asJson()
            ->timeout((int) config('services.openai.timeout', 60))
            ->post('https://api.openai.com/v1/responses', $payload);

        if ($response->failed()) {
            throw new RuntimeException($response->json('error.message') ?? 'OpenAI request failed.');
        }

        $data = $response->json();
        $text = $this->extractText($data);
        $decoded = json_decode($text, true);

        if (! is_array($decoded)) {
            throw new RuntimeException('OpenAI did not return valid JSON.');
        }

        return [
            'id' => Arr::get($data, 'id'),
            'model' => Arr::get($data, 'model', $payload['model']),
            'json' => $decoded,
            'text' => $text,
            'usage' => Arr::get($data, 'usage', []),
            'payload' => $payload,
            'raw' => $data,
        ];
    }

    private function extractText(array $response): string
    {
        if (filled(Arr::get($response, 'output_text'))) {
            return Arr::get($response, 'output_text');
        }

        $parts = [];

        foreach (Arr::get($response, 'output', []) as $item) {
            foreach (Arr::get($item, 'content', []) as $content) {
                $text = Arr::get($content, 'text');

                if (filled($text)) {
                    $parts[] = $text;
                }
            }
        }

        return trim(implode("\n", $parts)) ?: 'I could not generate a response. Please try again.';
    }
}
