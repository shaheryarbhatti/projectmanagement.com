<?php

namespace App\Services\ExcelInsight;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiFallbackService
{
    public function reply(string $question, array $history, string $context): ?array
    {
        $providers = [
            ['name' => 'openai', 'method' => 'callOpenAi'],
            ['name' => 'groq', 'method' => 'callGroq'],
            ['name' => 'grok', 'method' => 'callGrok'],
            ['name' => 'claude', 'method' => 'callClaude'],
        ];

        foreach ($providers as $provider) {
            try {
                $answer = $this->{$provider['method']}($question, $history, $context);
                if ($answer !== null) {
                    return ['answer' => $answer, 'provider' => $provider['name']];
                }
            } catch (\Throwable $e) {
                Log::error("AI Provider {$provider['name']} failed: " . $e->getMessage());
            }
        }

        return null; // Return null so the assistant can fallback to raw DB data
    }

    private function callOpenAi(string $question, array $history, string $context): ?string
    {
        $apiKey = (string) config('excel_insights.openai.api_key');
        $model = (string) config('excel_insights.openai.model');
        if ($apiKey === '' || $model === '') {
            return null;
        }

        $payload = [
            'model' => $model,
            'messages' => $this->chatMessages($question, $history, $context),
            'temperature' => 0.3,
        ];

        $response = $this->performRequest('openai', fn () => Http::acceptJson()->withToken($apiKey)->timeout(40)->retry(2, 400, fn ($e) => $e instanceof ConnectionException)->post(rtrim((string) config('excel_insights.openai.base_url'), '/') . '/chat/completions', $payload));
        
        return $response?->successful() ? trim((string) data_get($response->json(), 'choices.0.message.content', '')) ?: null : null;
    }

    private function callGrok(string $question, array $history, string $context): ?string
    {
        $apiKey = (string) config('excel_insights.grok.api_key');
        $model = (string) config('excel_insights.grok.model');
        if ($apiKey === '' || $model === '') {
            return null;
        }

        $payload = [
            'model' => $model,
            'messages' => $this->chatMessages($question, $history, $context),
            'temperature' => 0.2,
        ];

        $response = $this->performRequest('grok', fn () => Http::acceptJson()->withToken($apiKey)->timeout(40)->retry(2, 400, fn ($e) => $e instanceof ConnectionException)->post(rtrim((string) config('excel_insights.grok.base_url'), '/') . '/chat/completions', $payload));
        return $response?->successful() ? trim((string) data_get($response->json(), 'choices.0.message.content', '')) ?: null : null;
    }

    private function callGroq(string $question, array $history, string $context): ?string
    {
        $apiKey = (string) config('excel_insights.groq.api_key');
        $model = (string) config('excel_insights.groq.model');
        if ($apiKey === '' || $model === '') {
            return null;
        }

        $payload = [
            'model' => $model,
            'messages' => $this->chatMessages($question, $history, $context),
            'temperature' => 0.2,
        ];

        $response = $this->performRequest('groq', fn () => Http::acceptJson()->withToken($apiKey)->timeout(40)->retry(2, 400, fn ($e) => $e instanceof ConnectionException)->post(rtrim((string) config('excel_insights.groq.base_url'), '/') . '/chat/completions', $payload));
        return $response?->successful() ? trim((string) data_get($response->json(), 'choices.0.message.content', '')) ?: null : null;
    }

    private function callClaude(string $question, array $history, string $context): ?string
    {
        $apiKey = (string) config('excel_insights.claude.api_key');
        $model = (string) config('excel_insights.claude.model');
        if ($apiKey === '' || $model === '') {
            return null;
        }

        $payload = [
            'model' => $model,
            'max_tokens' => 700,
            'system' => $this->systemPrompt($context),
            'messages' => array_map(fn ($message) => ['role' => $message['role'], 'content' => $message['content']], $this->chatMessages($question, $history, $context, false)),
        ];

        $response = $this->performRequest('claude', fn () => Http::acceptJson()->withHeaders(['x-api-key' => $apiKey, 'anthropic-version' => (string) config('excel_insights.claude.version')])->timeout(40)->retry(2, 400, fn ($e) => $e instanceof ConnectionException)->post(rtrim((string) config('excel_insights.claude.base_url'), '/') . '/messages', $payload));
        return $response?->successful() ? trim((string) data_get($response->json(), 'content.0.text', '')) ?: null : null;
    }

    private function performRequest(string $provider, callable $request): ?Response
    {
        try {
            $response = $request();
            if ($response->status() === 429 && $this->isHardQuotaError($response)) {
                Log::warning('Excel Insight AI quota reached.', ['provider' => $provider, 'body' => $response->json() ?: $response->body()]);
                return null;
            }
            if (! $response->successful()) {
                Log::warning('Excel Insight AI provider failed.', ['provider' => $provider, 'status' => $response->status(), 'body' => $response->json() ?: $response->body()]);
            }
            return $response;
        } catch (\Throwable $exception) {
            Log::warning('Excel Insight AI provider exception.', ['provider' => $provider, 'message' => $exception->getMessage()]);
            return null;
        }
    }

    private function isHardQuotaError(Response $response): bool
    {
        $message = strtolower((string) data_get($response->json(), 'error.message', $response->body()));
        return str_contains($message, 'quota') || str_contains($message, 'billing') || str_contains($message, 'credit');
    }

    private function openAiMessages(string $question, array $history, string $context): array
    {
        $messages = [['role' => 'system', 'content' => $this->systemPrompt($context)]];
        foreach (array_slice($history, -8) as $message) {
            $messages[] = ['role' => $message['role'], 'content' => $message['content']];
        }
        $messages[] = ['role' => 'user', 'content' => $question];
        return $messages;
    }

    private function chatMessages(string $question, array $history, string $context, bool $includeSystem = true): array
    {
        $messages = [];
        if ($includeSystem) {
            $messages[] = ['role' => 'system', 'content' => $this->systemPrompt($context)];
        }
        foreach (array_slice($history, -8) as $message) {
            $messages[] = ['role' => $message['role'], 'content' => $message['content']];
        }
        $messages[] = ['role' => 'user', 'content' => $question];
        return $messages;
    }

    private function systemPrompt(string $context): string
    {
        return trim(implode("\n\n", [
            'You are an Excel data analysis assistant inside a Laravel dashboard.',
            'Answer using the uploaded workbook data only. Keep the tone professional and concise.',
            'If the data does not support a conclusion, say so clearly.',
            "Database summary:\n" . $context,
        ]));
    }
}
