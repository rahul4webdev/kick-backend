<?php

namespace App\Http\Controllers;

use App\Models\GlobalSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ContentModerationController extends Controller
{
    /**
     * Check content (image, video, or text) for moderation.
     *
     * Tries Cloudflare Worker first, falls back to self-hosted service.
     * If both fail or moderation is disabled, accepts by default.
     */
    public function checkContent(Request $request)
    {
        $setting = GlobalSettings::first();

        // If moderation is disabled, accept everything
        if (!$setting || !$setting->is_content_moderation) {
            return response()->json([
                'status' => true,
                'data' => ['action' => 'accept', 'reasons' => []],
            ]);
        }

        $type = $request->input('type', 'image'); // image, video, text
        $content = $request->input('content');     // for text moderation

        // Try Cloudflare Worker first
        $cloudflareUrl = $setting->moderation_cloudflare_url;
        $cloudflareToken = $setting->moderation_cloudflare_token;

        if ($cloudflareUrl) {
            $result = $this->tryCloudflare($request, $cloudflareUrl, $cloudflareToken, $type, $content);
            if ($result !== null) {
                return response()->json(['status' => true, 'data' => $result]);
            }
        }

        // Fall back to self-hosted service
        $selfHostedUrl = $setting->moderation_self_hosted_url;

        if ($selfHostedUrl) {
            $result = $this->trySelfHosted($request, $selfHostedUrl, $type, $content);
            if ($result !== null) {
                return response()->json(['status' => true, 'data' => $result]);
            }
        }

        // Both failed — accept by default (graceful degradation)
        Log::warning('Content moderation: both providers failed, accepting by default');
        return response()->json([
            'status' => true,
            'data' => ['action' => 'accept', 'reasons' => []],
        ]);
    }

    private function tryCloudflare(Request $request, string $url, ?string $token, string $type, ?string $content): ?array
    {
        try {
            $headers = ['Accept' => 'application/json'];
            if ($token) {
                $headers['Authorization'] = 'Bearer ' . $token;
            }

            if ($type === 'text') {
                $response = Http::withHeaders($headers)
                    ->timeout(15)
                    ->post($url, [
                        'type' => 'text',
                        'content' => $content,
                    ]);
            } else {
                // Image or video — forward the uploaded file
                $file = $request->file('file');
                if (!$file) {
                    return null;
                }

                $response = Http::withHeaders($headers)
                    ->timeout(30)
                    ->attach('file', file_get_contents($file->getRealPath()), $file->getClientOriginalName())
                    ->post($url, ['type' => $type]);
            }

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'action' => $data['action'] ?? 'accept',
                    'reasons' => $data['reasons'] ?? [],
                ];
            }

            Log::warning('Cloudflare moderation failed: ' . $response->status());
            return null;
        } catch (\Exception $e) {
            Log::warning('Cloudflare moderation error: ' . $e->getMessage());
            return null;
        }
    }

    private function trySelfHosted(Request $request, string $url, string $type, ?string $content): ?array
    {
        try {
            $checkUrl = rtrim($url, '/') . '/check';

            if ($type === 'text') {
                $response = Http::timeout(15)
                    ->post($checkUrl, [
                        'type' => 'text',
                        'content' => $content,
                    ]);
            } else {
                $file = $request->file('file');
                if (!$file) {
                    return null;
                }

                $response = Http::timeout(30)
                    ->attach('file', file_get_contents($file->getRealPath()), $file->getClientOriginalName())
                    ->post($checkUrl, ['type' => $type]);
            }

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'action' => $data['action'] ?? 'accept',
                    'reasons' => $data['reasons'] ?? [],
                ];
            }

            Log::warning('Self-hosted moderation failed: ' . $response->status());
            return null;
        } catch (\Exception $e) {
            Log::warning('Self-hosted moderation error: ' . $e->getMessage());
            return null;
        }
    }
}
