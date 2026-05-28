<?php

namespace App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class OpenAiService
{
    public function createResponse(string $message, ?string $previousResponseId = null, array $context = []): array
    {
        $apiKey = config('services.openai.api_key');

        if (blank($apiKey)) {
            throw new RuntimeException('OpenAI API key is not configured.');
        }

        $payload = array_filter([
            'model' => config('services.openai.model', 'gpt-4.1-mini'),
            'instructions' => $this->buildAssistantInstructions($context),
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

        $lastData = null;
        $lastText = null;

        for ($attempt = 1; $attempt <= 2; $attempt++) {
            $response = Http::withToken($apiKey)
                ->acceptJson()
                ->asJson()
                ->timeout((int) config('services.openai.timeout', 60))
                ->post('https://api.openai.com/v1/responses', $payload);

            if ($response->failed()) {
                throw new RuntimeException($response->json('error.message') ?? 'OpenAI request failed.');
            }

            $lastData = $response->json();
            $lastText = $this->extractText($lastData);
            $decoded = json_decode($lastText, true);

            if (is_array($decoded)) {
                return [
                    'id' => Arr::get($lastData, 'id'),
                    'model' => Arr::get($lastData, 'model', $payload['model']),
                    'json' => $decoded,
                    'text' => $lastText,
                    'usage' => Arr::get($lastData, 'usage', []),
                    'payload' => $payload,
                    'raw' => $lastData,
                ];
            }

            $payload['input'] = "The previous response was not valid JSON. Return JSON only that matches the required schema.\n\nOriginal input:\n" . $payload['input'];
        }

        throw new RuntimeException('OpenAI did not return valid JSON.');
    }

    private function buildAssistantInstructions(array $context): string
    {
        $appName = $context['app_name'] ?? config('app.name', 'CRM');
        $userName = $context['user_name'] ?? null;
        $username = $context['username'] ?? null;
        $roles = implode(', ', array_filter($context['roles'] ?? []));

        $identity = [
            "Application name: {$appName}",
            $userName ? "Logged-in user name: {$userName}" : null,
            $username ? "Logged-in username: {$username}" : null,
            $roles ? "Logged-in user roles: {$roles}" : null,
        ];

        return implode("\n", array_filter([
            'You are SolenAssist, the built-in AI assistant for this Laravel CRM.',
            'Answer clearly, professionally, and helpfully.',
            'You may greet the logged-in user by name when it is natural.',
            'Use the safe identity context below only for greeting and personalization.',
            'Do not claim that you lack access to the logged-in user name when it is provided in this context.',
            'Do not invent CRM records or private details. CRM data questions are handled by the secure query pipeline.',
            implode("\n", array_filter($identity)),
        ]));
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
