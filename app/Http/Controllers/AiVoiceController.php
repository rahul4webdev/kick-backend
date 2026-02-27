<?php

namespace App\Http\Controllers;

use App\Models\GlobalFunction;
use App\Models\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class AiVoiceController extends Controller
{
    public function enhanceAudio(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));
        if ($user->is_freez == 1) {
            return response()->json(['status' => false, 'message' => 'Account frozen']);
        }

        $request->validate([
            'audio' => 'required|file|max:20480',
        ]);

        $settings = Settings::first();

        if (!$settings->ai_voice_enhancement_enabled) {
            return response()->json(['status' => false, 'message' => 'Voice enhancement is currently disabled']);
        }

        try {
            $file = $request->file('audio');
            $extension = $file->getClientOriginalExtension() ?: 'wav';
            $inputName = 'voice_input_' . time() . '_' . uniqid() . '.' . $extension;
            $outputName = 'voice_enhanced_' . time() . '_' . uniqid() . '.wav';

            $storageType = env('FILES_STORAGE_LOCATION', 'PUBLIC');
            $uploadDir = 'uploads/audio/';

            if ($storageType === 'PUBLIC') {
                $inputPath = $file->storeAs($uploadDir, $inputName, 'public');
                $inputFullPath = storage_path('app/public/' . $inputPath);
                $outputFullPath = storage_path('app/public/' . $uploadDir . $outputName);

                // FFmpeg audio enhancement:
                // 1. High-pass filter to remove low rumble (below 80Hz)
                // 2. Noise reduction via anlmdn (adaptive non-local means denoising)
                // 3. Compressor for dynamic range
                // 4. Loudness normalization
                $ffmpegCmd = sprintf(
                    'ffmpeg -i %s -af "highpass=f=80,anlmdn=s=7:p=0.002:r=0.002:o=o,acompressor=threshold=-20dB:ratio=4:attack=5:release=50,loudnorm=I=-16:TP=-1.5:LRA=11" -ar 44100 -ac 1 %s -y 2>&1',
                    escapeshellarg($inputFullPath),
                    escapeshellarg($outputFullPath)
                );

                exec($ffmpegCmd, $output, $returnCode);

                if ($returnCode !== 0) {
                    // Fallback: simple volume normalization only
                    $fallbackCmd = sprintf(
                        'ffmpeg -i %s -af "loudnorm=I=-16:TP=-1.5:LRA=11" -ar 44100 %s -y 2>&1',
                        escapeshellarg($inputFullPath),
                        escapeshellarg($outputFullPath)
                    );
                    exec($fallbackCmd, $output2, $returnCode2);

                    if ($returnCode2 !== 0) {
                        @unlink($inputFullPath);
                        return response()->json(['status' => false, 'message' => 'Audio enhancement failed']);
                    }
                }

                // Clean up input file
                @unlink($inputFullPath);

                $enhancedUrl = $uploadDir . $outputName;

                return response()->json([
                    'status' => true,
                    'message' => 'Audio enhanced successfully',
                    'data' => [
                        'enhanced_url' => $enhancedUrl,
                    ],
                ]);
            } else {
                // For S3/DO Spaces: save locally, process, upload to cloud
                $tempInput = tempnam(sys_get_temp_dir(), 'voice_in_');
                $tempOutput = tempnam(sys_get_temp_dir(), 'voice_out_') . '.wav';
                file_put_contents($tempInput, file_get_contents($file->getRealPath()));

                $ffmpegCmd = sprintf(
                    'ffmpeg -i %s -af "highpass=f=80,anlmdn=s=7:p=0.002:r=0.002:o=o,acompressor=threshold=-20dB:ratio=4:attack=5:release=50,loudnorm=I=-16:TP=-1.5:LRA=11" -ar 44100 -ac 1 %s -y 2>&1',
                    escapeshellarg($tempInput),
                    escapeshellarg($tempOutput)
                );

                exec($ffmpegCmd, $output, $returnCode);

                if ($returnCode !== 0) {
                    $fallbackCmd = sprintf(
                        'ffmpeg -i %s -af "loudnorm=I=-16:TP=-1.5:LRA=11" -ar 44100 %s -y 2>&1',
                        escapeshellarg($tempInput),
                        escapeshellarg($tempOutput)
                    );
                    exec($fallbackCmd, $output2, $returnCode2);

                    if ($returnCode2 !== 0) {
                        @unlink($tempInput);
                        @unlink($tempOutput);
                        return response()->json(['status' => false, 'message' => 'Audio enhancement failed']);
                    }
                }

                @unlink($tempInput);

                $disk = ($storageType === 'AWSS3') ? 's3' : 'do';
                $cloudPath = $uploadDir . $outputName;
                Storage::disk($disk)->put($cloudPath, file_get_contents($tempOutput), 'public');

                @unlink($tempOutput);

                $enhancedUrl = Storage::disk($disk)->url($cloudPath);

                return response()->json([
                    'status' => true,
                    'message' => 'Audio enhanced successfully',
                    'data' => [
                        'enhanced_url' => $enhancedUrl,
                    ],
                ]);
            }
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Voice enhancement service unavailable']);
        }
    }

    public function enhanceVideo(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));
        if ($user->is_freez == 1) {
            return response()->json(['status' => false, 'message' => 'Account frozen']);
        }

        $request->validate([
            'video' => 'required|file|max:102400', // 100MB max
        ]);

        $settings = Settings::first();

        if (!$settings->ai_voice_enhancement_enabled) {
            return response()->json(['status' => false, 'message' => 'AI enhancement is currently disabled']);
        }

        try {
            $file = $request->file('video');
            $extension = $file->getClientOriginalExtension() ?: 'mp4';
            $inputName = 'video_input_' . time() . '_' . uniqid() . '.' . $extension;
            $outputName = 'video_enhanced_' . time() . '_' . uniqid() . '.mp4';

            $storageType = env('FILES_STORAGE_LOCATION', 'PUBLIC');
            $uploadDir = 'uploads/video/';

            if ($storageType === 'PUBLIC') {
                $inputPath = $file->storeAs($uploadDir, $inputName, 'public');
                $inputFullPath = storage_path('app/public/' . $inputPath);
                $outputFullPath = storage_path('app/public/' . $uploadDir . $outputName);

                // FFmpeg video auto-enhance:
                // 1. eq: brightness +0.06, contrast 1.3, saturation 1.2
                // 2. unsharp: luma sharpen 5x5 strength 0.8, chroma 3x3 strength 0.4
                $ffmpegCmd = sprintf(
                    'ffmpeg -i %s -vf "eq=brightness=0.06:contrast=1.3:saturation=1.2,unsharp=5:5:0.8:3:3:0.4" -c:a copy %s -y 2>&1',
                    escapeshellarg($inputFullPath),
                    escapeshellarg($outputFullPath)
                );

                exec($ffmpegCmd, $output, $returnCode);

                if ($returnCode !== 0) {
                    // Fallback: simpler enhancement without sharpening
                    $fallbackCmd = sprintf(
                        'ffmpeg -i %s -vf "eq=brightness=0.04:contrast=1.2:saturation=1.1" -c:a copy %s -y 2>&1',
                        escapeshellarg($inputFullPath),
                        escapeshellarg($outputFullPath)
                    );
                    exec($fallbackCmd, $output2, $returnCode2);

                    if ($returnCode2 !== 0) {
                        @unlink($inputFullPath);
                        return response()->json(['status' => false, 'message' => 'Video enhancement failed']);
                    }
                }

                // Clean up input file
                @unlink($inputFullPath);

                $enhancedUrl = $uploadDir . $outputName;

                return response()->json([
                    'status' => true,
                    'message' => 'Video enhanced successfully',
                    'data' => [
                        'enhanced_url' => $enhancedUrl,
                    ],
                ]);
            } else {
                // For S3/DO Spaces: save locally, process, upload to cloud
                $tempInput = tempnam(sys_get_temp_dir(), 'video_in_');
                $tempOutput = tempnam(sys_get_temp_dir(), 'video_out_') . '.mp4';
                file_put_contents($tempInput, file_get_contents($file->getRealPath()));

                $ffmpegCmd = sprintf(
                    'ffmpeg -i %s -vf "eq=brightness=0.06:contrast=1.3:saturation=1.2,unsharp=5:5:0.8:3:3:0.4" -c:a copy %s -y 2>&1',
                    escapeshellarg($tempInput),
                    escapeshellarg($tempOutput)
                );

                exec($ffmpegCmd, $output, $returnCode);

                if ($returnCode !== 0) {
                    $fallbackCmd = sprintf(
                        'ffmpeg -i %s -vf "eq=brightness=0.04:contrast=1.2:saturation=1.1" -c:a copy %s -y 2>&1',
                        escapeshellarg($tempInput),
                        escapeshellarg($tempOutput)
                    );
                    exec($fallbackCmd, $output2, $returnCode2);

                    if ($returnCode2 !== 0) {
                        @unlink($tempInput);
                        @unlink($tempOutput);
                        return response()->json(['status' => false, 'message' => 'Video enhancement failed']);
                    }
                }

                @unlink($tempInput);

                $disk = ($storageType === 'AWSS3') ? 's3' : 'do';
                $cloudPath = $uploadDir . $outputName;
                Storage::disk($disk)->put($cloudPath, file_get_contents($tempOutput), 'public');

                @unlink($tempOutput);

                $enhancedUrl = Storage::disk($disk)->url($cloudPath);

                return response()->json([
                    'status' => true,
                    'message' => 'Video enhanced successfully',
                    'data' => [
                        'enhanced_url' => $enhancedUrl,
                    ],
                ]);
            }
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Video enhancement service unavailable']);
        }
    }

    public function transcribeAudio(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));
        if ($user->is_freez == 1) {
            return response()->json(['status' => false, 'message' => 'Account frozen']);
        }

        $request->validate([
            'audio_url' => 'required|string|max:1000',
        ]);

        $settings = Settings::first();

        $apiKey = $settings->ai_image_api_key;
        $provider = $settings->ai_image_provider ?? 'openai';

        if (empty($apiKey)) {
            // Fallback: use Claude to describe that we need a speech-to-text API
            return response()->json(['status' => false, 'message' => 'Speech-to-text service not configured']);
        }

        try {
            // Download the audio file to a temp location
            $audioContent = file_get_contents($request->audio_url);
            if ($audioContent === false) {
                return response()->json(['status' => false, 'message' => 'Could not download audio file']);
            }

            $tempFile = tempnam(sys_get_temp_dir(), 'transcribe_') . '.wav';
            file_put_contents($tempFile, $audioContent);

            if ($provider === 'openai') {
                // Use OpenAI Whisper API for transcription
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                ])->timeout(60)->attach(
                    'file', file_get_contents($tempFile), 'audio.wav'
                )->post('https://api.openai.com/v1/audio/transcriptions', [
                    'model' => 'whisper-1',
                    'response_format' => 'json',
                ]);

                @unlink($tempFile);

                if ($response->successful()) {
                    $data = $response->json();
                    $transcription = $data['text'] ?? '';

                    return response()->json([
                        'status' => true,
                        'message' => 'Success',
                        'data' => [
                            'transcription' => $transcription,
                        ],
                    ]);
                }

                return response()->json(['status' => false, 'message' => 'Transcription service error']);
            }

            @unlink($tempFile);
            return response()->json(['status' => false, 'message' => 'Unsupported transcription provider']);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Transcription service unavailable']);
        }
    }
}
