<?php

namespace App\Services;

use App\Models\GlobalSettings;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    /**
     * Send a request to the AI API (Gemini primary, Groq fallback).
     *
     * @param  string  $systemPrompt  The system instruction
     * @param  array   $messages      Array of ['role' => 'user'|'assistant', 'content' => '...']
     * @param  int     $maxTokens     Max output tokens
     * @param  int     $timeout       HTTP timeout in seconds
     * @return array{success: bool, text: string|null, error: string|null}
     */
    public static function generateContent(
        string $systemPrompt,
        array $messages,
        int $maxTokens = 1024,
        int $timeout = 30
    ): array {
        $settings = GlobalSettings::getCached();

        // Try Gemini first
        $geminiKey = $settings->ai_api_key ?? null;
        if (!empty($geminiKey)) {
            $result = self::callGemini($settings, $systemPrompt, $messages, $maxTokens, $timeout);
            if ($result['success']) {
                return $result;
            }
            Log::info('Gemini failed, attempting Groq fallback', ['error' => $result['error']]);
        }

        // Fallback to Groq
        $groqKey = $settings->groq_api_key ?? null;
        if (!empty($groqKey)) {
            return self::callGroq($settings, $systemPrompt, $messages, $maxTokens, $timeout);
        }

        return ['success' => false, 'text' => null, 'error' => 'AI service not configured'];
    }

    private static function callGemini(
        $settings,
        string $systemPrompt,
        array $messages,
        int $maxTokens,
        int $timeout
    ): array {
        $apiKey = $settings->ai_api_key;
        $model = $settings->ai_model ?? 'gemini-2.0-flash';

        // Build Gemini contents array (Claude "assistant" → Gemini "model")
        $contents = [];
        foreach ($messages as $msg) {
            $role = ($msg['role'] === 'assistant') ? 'model' : $msg['role'];
            $contents[] = [
                'role' => $role,
                'parts' => [['text' => $msg['content']]],
            ];
        }

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

        $body = [
            'contents' => $contents,
            'generationConfig' => [
                'maxOutputTokens' => $maxTokens,
            ],
        ];

        if (!empty($systemPrompt)) {
            $body['systemInstruction'] = [
                'parts' => [['text' => $systemPrompt]],
            ];
        }

        try {
            $response = Http::timeout($timeout)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($url, $body);

            if ($response->successful()) {
                $data = $response->json();
                $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
                return ['success' => true, 'text' => $text, 'error' => null];
            }

            Log::warning('Gemini API error', [
                'status' => $response->status(),
                'body' => substr($response->body(), 0, 500),
            ]);
            return ['success' => false, 'text' => null, 'error' => 'Gemini API error'];

        } catch (\Exception $e) {
            Log::error('Gemini API exception', ['error' => $e->getMessage()]);
            return ['success' => false, 'text' => null, 'error' => 'Gemini unavailable'];
        }
    }

    private static function callGroq(
        $settings,
        string $systemPrompt,
        array $messages,
        int $maxTokens,
        int $timeout
    ): array {
        $apiKey = $settings->groq_api_key;
        $model = $settings->groq_model ?? 'llama-3.3-70b-versatile';

        // Build OpenAI-compatible messages array
        $openaiMessages = [];
        if (!empty($systemPrompt)) {
            $openaiMessages[] = ['role' => 'system', 'content' => $systemPrompt];
        }
        foreach ($messages as $msg) {
            $openaiMessages[] = [
                'role' => $msg['role'],
                'content' => $msg['content'],
            ];
        }

        try {
            $response = Http::timeout($timeout)
                ->withHeaders([
                    'Authorization' => "Bearer {$apiKey}",
                    'Content-Type' => 'application/json',
                ])
                ->post('https://api.groq.com/openai/v1/chat/completions', [
                    'model' => $model,
                    'messages' => $openaiMessages,
                    'max_tokens' => $maxTokens,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $text = $data['choices'][0]['message']['content'] ?? null;
                return ['success' => true, 'text' => $text, 'error' => null];
            }

            Log::warning('Groq API error', [
                'status' => $response->status(),
                'body' => substr($response->body(), 0, 500),
            ]);
            return ['success' => false, 'text' => null, 'error' => 'AI service error'];

        } catch (\Exception $e) {
            Log::error('Groq API exception', ['error' => $e->getMessage()]);
            return ['success' => false, 'text' => null, 'error' => 'AI service unavailable'];
        }
    }
}
