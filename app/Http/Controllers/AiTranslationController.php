<?php

namespace App\Http\Controllers;

use App\Models\GlobalFunction;
use App\Models\GlobalSettings;
use App\Services\GeminiService;
use Illuminate\Http\Request;

class AiTranslationController extends Controller
{
    public function translateText(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));
        if ($user->is_freez == 1) {
            return response()->json(['status' => false, 'message' => 'Account frozen']);
        }

        $request->validate([
            'text' => 'required|string|max:5000',
            'target_language' => 'required|string|max:50',
            'source_language' => 'nullable|string|max:50',
        ]);

        $settings = GlobalSettings::getCached();

        if (!$settings->ai_translation_enabled) {
            return response()->json(['status' => false, 'message' => 'Translation is currently disabled']);
        }

        if (empty($settings->ai_api_key)) {
            return response()->json(['status' => false, 'message' => 'AI service not configured']);
        }

        $sourceHint = $request->source_language
            ? "The source language is {$request->source_language}."
            : "Auto-detect the source language.";

        $systemPrompt = "You are a professional translator. Translate the given text to {$request->target_language}. {$sourceHint} Return ONLY the translated text, nothing else. No explanations, no notes.";

        try {
            $result = GeminiService::generateContent($systemPrompt, [
                ['role' => 'user', 'content' => $request->text],
            ], 4096);

            if ($result['success']) {
                $translated = $result['text'] ?? '';

                return response()->json([
                    'status' => true,
                    'message' => 'Success',
                    'data' => [
                        'original' => $request->text,
                        'translated' => $translated,
                        'target_language' => $request->target_language,
                    ],
                ]);
            }

            return response()->json(['status' => false, 'message' => $result['error'] ?? 'Translation service error']);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Translation service unavailable']);
        }
    }

    public function translateCaptions(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));
        if ($user->is_freez == 1) {
            return response()->json(['status' => false, 'message' => 'Account frozen']);
        }

        $request->validate([
            'captions' => 'required|string',
            'target_language' => 'required|string|max:50',
        ]);

        $settings = GlobalSettings::getCached();

        if (!$settings->ai_translation_enabled) {
            return response()->json(['status' => false, 'message' => 'Translation is currently disabled']);
        }

        if (empty($settings->ai_api_key)) {
            return response()->json(['status' => false, 'message' => 'AI service not configured']);
        }

        $captions = json_decode($request->captions, true);
        if (!is_array($captions) || empty($captions)) {
            return response()->json(['status' => false, 'message' => 'Invalid captions format']);
        }

        // Extract just the text portions for translation
        $texts = array_map(fn($c) => $c['text'] ?? '', $captions);
        $combined = implode("\n---SEPARATOR---\n", $texts);

        $systemPrompt = "You are a professional subtitle translator. Translate each subtitle segment to {$request->target_language}. The segments are separated by ---SEPARATOR---. Return ONLY the translated segments separated by the same ---SEPARATOR--- marker. Maintain the same number of segments. No extra text.";

        try {
            $result = GeminiService::generateContent($systemPrompt, [
                ['role' => 'user', 'content' => $combined],
            ], 4096);

            if ($result['success']) {
                $translatedCombined = $result['text'] ?? '';
                $translatedTexts = explode('---SEPARATOR---', $translatedCombined);

                $translatedCaptions = [];
                foreach ($captions as $i => $caption) {
                    $translatedCaptions[] = [
                        'start_ms' => $caption['start_ms'] ?? $caption['startMs'] ?? 0,
                        'end_ms' => $caption['end_ms'] ?? $caption['endMs'] ?? 0,
                        'text' => trim($translatedTexts[$i] ?? $caption['text'] ?? ''),
                    ];
                }

                return response()->json([
                    'status' => true,
                    'message' => 'Success',
                    'data' => [
                        'captions' => $translatedCaptions,
                        'target_language' => $request->target_language,
                    ],
                ]);
            }

            return response()->json(['status' => false, 'message' => $result['error'] ?? 'Translation service error']);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Translation service unavailable']);
        }
    }
}
