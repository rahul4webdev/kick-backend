<?php

namespace App\Http\Controllers;

use App\Models\AiSticker;
use App\Models\GlobalFunction;
use App\Models\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class AiStickerController extends Controller
{
    public function generateSticker(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));
        if ($user->is_freez == 1) {
            return response()->json(['status' => false, 'message' => 'Account frozen']);
        }

        $request->validate([
            'prompt' => 'required|string|max:500',
        ]);

        $settings = Settings::first();

        if (!$settings->ai_sticker_enabled) {
            return response()->json(['status' => false, 'message' => 'AI sticker generation is disabled']);
        }

        $apiKey = $settings->ai_image_api_key;
        if (empty($apiKey)) {
            return response()->json(['status' => false, 'message' => 'AI image service not configured']);
        }

        $provider = $settings->ai_image_provider ?? 'openai';
        $prompt = 'Create a sticker-style illustration with transparent background: ' . $request->prompt
            . '. Style: cute, vibrant, sticker art, clean edges, no text unless specified.';

        try {
            $imageUrl = null;

            if ($provider === 'openai') {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ])->timeout(60)->post('https://api.openai.com/v1/images/generations', [
                    'model' => $settings->ai_image_model ?? 'dall-e-3',
                    'prompt' => $prompt,
                    'n' => 1,
                    'size' => '1024x1024',
                    'response_format' => 'b64_json',
                ]);

                if (!$response->successful()) {
                    return response()->json(['status' => false, 'message' => 'Image generation failed']);
                }

                $imageData = $response->json()['data'][0]['b64_json'] ?? null;
                if (!$imageData) {
                    return response()->json(['status' => false, 'message' => 'No image generated']);
                }

                // Save image to storage
                $imageUrl = $this->saveGeneratedImage($imageData);
            } elseif ($provider === 'stability') {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])->timeout(60)->post('https://api.stability.ai/v2beta/stable-image/generate/sd3', [
                    'prompt' => $prompt,
                    'output_format' => 'png',
                    'aspect_ratio' => '1:1',
                ]);

                if (!$response->successful()) {
                    return response()->json(['status' => false, 'message' => 'Image generation failed']);
                }

                $imageData = $response->json()['image'] ?? null;
                if (!$imageData) {
                    return response()->json(['status' => false, 'message' => 'No image generated']);
                }

                $imageUrl = $this->saveGeneratedImage($imageData);
            }

            if (!$imageUrl) {
                return response()->json(['status' => false, 'message' => 'Failed to save sticker']);
            }

            // Create sticker record
            $sticker = AiSticker::create([
                'user_id' => $user->id,
                'prompt' => $request->prompt,
                'image_url' => $imageUrl,
                'is_public' => $request->is_public ?? false,
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Sticker generated',
                'data' => [
                    'id' => $sticker->id,
                    'prompt' => $sticker->prompt,
                    'image_url' => $sticker->image_url,
                    'created_at' => $sticker->created_at->toISOString(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Sticker generation failed']);
        }
    }

    private function saveGeneratedImage(string $base64Data): ?string
    {
        $storageType = env('FILES_STORAGE_LOCATION');
        $rawAppName = env('APP_NAME');
        $cleanAppName = $rawAppName ? preg_replace('/[^A-Za-z0-9_\-]/', '_', $rawAppName) : '';
        $fileName = 'sticker_' . time() . '_' . uniqid() . '.png';
        $appNamePath = $cleanAppName ? $cleanAppName . '/' : '';
        $filePath = $storageType === 'PUBLIC'
            ? 'uploads/stickers/' . $fileName
            : $appNamePath . 'uploads/stickers/' . $fileName;

        $imageContent = base64_decode($base64Data);

        switch ($storageType) {
            case 'AWSS3':
                Storage::disk('s3')->put($filePath, $imageContent, 'public');
                break;
            case 'DOSPACE':
                Storage::disk('digitalocean')->put($filePath, $imageContent, 'public');
                break;
            case 'PUBLIC':
                Storage::disk('public')->put($filePath, $imageContent, 'public');
                break;
        }

        return $filePath;
    }

    public function fetchMyStickers(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));
        if ($user->is_freez == 1) {
            return response()->json(['status' => false, 'message' => 'Account frozen']);
        }

        $stickers = AiSticker::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit($request->limit ?? 30)
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'Success',
            'data' => $stickers->map(function ($s) {
                return [
                    'id' => $s->id,
                    'prompt' => $s->prompt,
                    'image_url' => $s->image_url,
                    'use_count' => $s->use_count,
                    'created_at' => $s->created_at->toISOString(),
                ];
            }),
        ]);
    }

    public function fetchPublicStickers(Request $request)
    {
        $stickers = AiSticker::where('is_public', true)
            ->orderBy('use_count', 'desc')
            ->limit($request->limit ?? 30)
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'Success',
            'data' => $stickers->map(function ($s) {
                return [
                    'id' => $s->id,
                    'prompt' => $s->prompt,
                    'image_url' => $s->image_url,
                    'use_count' => $s->use_count,
                    'created_at' => $s->created_at->toISOString(),
                ];
            }),
        ]);
    }

    public function incrementUseCount(Request $request)
    {
        $sticker = AiSticker::find($request->sticker_id);
        if ($sticker) {
            $sticker->increment('use_count');
        }

        return response()->json(['status' => true, 'message' => 'Success']);
    }

    public function deleteSticker(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));
        if ($user->is_freez == 1) {
            return response()->json(['status' => false, 'message' => 'Account frozen']);
        }

        $sticker = AiSticker::where('id', $request->sticker_id)
            ->where('user_id', $user->id)
            ->first();

        if (!$sticker) {
            return response()->json(['status' => false, 'message' => 'Sticker not found']);
        }

        GlobalFunction::deleteFile($sticker->image_url);
        $sticker->delete();

        return response()->json(['status' => true, 'message' => 'Sticker deleted']);
    }
}
