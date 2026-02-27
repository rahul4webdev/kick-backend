<?php

namespace App\Http\Controllers;

use App\Models\GlobalFunction;
use App\Models\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AiVideoController extends Controller
{
    /**
     * Generate a short video from a text prompt using FFmpeg.
     * Creates an animated text-on-gradient video with smooth zoom/pan effects.
     */
    public function generateFromText(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));
        if ($user->is_freez == 1) {
            return response()->json(['status' => false, 'message' => 'Account frozen']);
        }

        $request->validate([
            'prompt' => 'required|string|max:500',
            'duration' => 'nullable|integer|min:3|max:15',
            'style' => 'nullable|string|in:gradient,dark,light,neon',
        ]);

        $settings = Settings::first();

        if (!($settings->ai_video_generation_enabled ?? true)) {
            return response()->json(['status' => false, 'message' => 'AI video generation is currently disabled']);
        }

        try {
            $prompt = $request->prompt;
            $duration = $request->duration ?? 5;
            $style = $request->style ?? 'gradient';
            $outputName = 'ai_video_' . time() . '_' . uniqid() . '.mp4';
            $storageType = env('FILES_STORAGE_LOCATION', 'PUBLIC');
            $uploadDir = 'uploads/video/';

            // Wrap text to fit the screen (max ~30 chars per line)
            $wrappedText = wordwrap($prompt, 28, "\n", true);
            $escapedText = str_replace(["'", '"', ':', '\\', '%'], ["'\\''", '\\"', '\\:', '\\\\', '%%'], $wrappedText);

            // Style-based color configurations
            $bgFilter = match ($style) {
                'dark' => "color=c=black:s=1080x1920:d=$duration",
                'light' => "color=c=white:s=1080x1920:d=$duration",
                'neon' => "color=c=#0a0a2e:s=1080x1920:d=$duration",
                default => "gradients=s=1080x1920:c0=#667eea:c1=#764ba2:x0=0:y0=0:x1=1080:y1=1920:d=$duration:speed=1",
            };

            $textColor = match ($style) {
                'light' => '#333333',
                'neon' => '#00ff88',
                default => '#ffffff',
            };

            $shadowColor = match ($style) {
                'light' => '#00000033',
                'neon' => '#00ff8866',
                default => '#00000066',
            };

            // Use simple color source since gradients filter may not be available
            $bgColor = match ($style) {
                'dark' => 'black',
                'light' => 'white',
                'neon' => '#0a0a2e',
                default => '#764ba2',
            };

            if ($storageType === 'PUBLIC') {
                $outputFullPath = storage_path('app/public/' . $uploadDir . $outputName);

                // Ensure directory exists
                $dir = dirname($outputFullPath);
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }

                // Generate video with animated text using FFmpeg
                // Uses color source + drawtext with fade-in animation
                $ffmpegCmd = sprintf(
                    'ffmpeg -f lavfi -i "color=c=%s:s=1080x1920:d=%d:r=30" ' .
                    '-vf "drawtext=text=\'%s\':fontsize=64:fontcolor=%s:' .
                    'shadowcolor=%s:shadowx=2:shadowy=2:' .
                    'x=(w-text_w)/2:y=(h-text_h)/2:' .
                    'alpha=\'if(lt(t\\,1)\\,t\\,if(gt(t\\,%d-1)\\,(%d-t)\\,1))\'" ' .
                    '-c:v libx264 -preset fast -pix_fmt yuv420p -t %d %s -y 2>&1',
                    escapeshellarg($bgColor),
                    $duration,
                    $escapedText,
                    $textColor,
                    $shadowColor,
                    $duration,
                    $duration,
                    $duration,
                    escapeshellarg($outputFullPath)
                );

                exec($ffmpegCmd, $output, $returnCode);

                if ($returnCode !== 0) {
                    // Fallback: simpler command without text animation
                    $fallbackCmd = sprintf(
                        'ffmpeg -f lavfi -i "color=c=%s:s=1080x1920:d=%d:r=30" ' .
                        '-vf "drawtext=text=\'%s\':fontsize=64:fontcolor=%s:x=(w-text_w)/2:y=(h-text_h)/2" ' .
                        '-c:v libx264 -preset fast -pix_fmt yuv420p -t %d %s -y 2>&1',
                        escapeshellarg($bgColor),
                        $duration,
                        $escapedText,
                        $textColor,
                        $duration,
                        escapeshellarg($outputFullPath)
                    );
                    exec($fallbackCmd, $output2, $returnCode2);

                    if ($returnCode2 !== 0) {
                        return response()->json(['status' => false, 'message' => 'Video generation failed']);
                    }
                }

                $videoUrl = $uploadDir . $outputName;

                return response()->json([
                    'status' => true,
                    'message' => 'Video generated successfully',
                    'data' => [
                        'video_url' => $videoUrl,
                        'duration' => $duration,
                    ],
                ]);
            } else {
                $tempOutput = tempnam(sys_get_temp_dir(), 'ai_vid_') . '.mp4';

                $ffmpegCmd = sprintf(
                    'ffmpeg -f lavfi -i "color=c=%s:s=1080x1920:d=%d:r=30" ' .
                    '-vf "drawtext=text=\'%s\':fontsize=64:fontcolor=%s:' .
                    'shadowcolor=%s:shadowx=2:shadowy=2:' .
                    'x=(w-text_w)/2:y=(h-text_h)/2:' .
                    'alpha=\'if(lt(t\\,1)\\,t\\,if(gt(t\\,%d-1)\\,(%d-t)\\,1))\'" ' .
                    '-c:v libx264 -preset fast -pix_fmt yuv420p -t %d %s -y 2>&1',
                    escapeshellarg($bgColor),
                    $duration,
                    $escapedText,
                    $textColor,
                    $shadowColor,
                    $duration,
                    $duration,
                    $duration,
                    escapeshellarg($tempOutput)
                );

                exec($ffmpegCmd, $output, $returnCode);

                if ($returnCode !== 0) {
                    @unlink($tempOutput);
                    return response()->json(['status' => false, 'message' => 'Video generation failed']);
                }

                $disk = ($storageType === 'AWSS3') ? 's3' : 'do';
                $cloudPath = $uploadDir . $outputName;
                Storage::disk($disk)->put($cloudPath, file_get_contents($tempOutput), 'public');
                @unlink($tempOutput);

                $videoUrl = Storage::disk($disk)->url($cloudPath);

                return response()->json([
                    'status' => true,
                    'message' => 'Video generated successfully',
                    'data' => [
                        'video_url' => $videoUrl,
                        'duration' => $duration,
                    ],
                ]);
            }
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Video generation service unavailable']);
        }
    }

    /**
     * Generate a video from an uploaded image using Ken Burns (pan/zoom) effect.
     */
    public function generateFromImage(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));
        if ($user->is_freez == 1) {
            return response()->json(['status' => false, 'message' => 'Account frozen']);
        }

        $request->validate([
            'image' => 'required|file|max:20480',
            'duration' => 'nullable|integer|min:3|max:15',
            'effect' => 'nullable|string|in:zoom_in,zoom_out,pan_left,pan_right',
        ]);

        $settings = Settings::first();

        if (!($settings->ai_video_generation_enabled ?? true)) {
            return response()->json(['status' => false, 'message' => 'AI video generation is currently disabled']);
        }

        try {
            $file = $request->file('image');
            $duration = $request->duration ?? 5;
            $effect = $request->effect ?? 'zoom_in';
            $inputName = 'img_input_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $outputName = 'ai_video_' . time() . '_' . uniqid() . '.mp4';
            $storageType = env('FILES_STORAGE_LOCATION', 'PUBLIC');
            $uploadDir = 'uploads/video/';

            // Ken Burns effect filter expressions
            $zoomExpr = match ($effect) {
                'zoom_out' => "zoompan=z='if(lte(zoom\\,1.0)\\,1.5\\,max(1.001\\,zoom-0.005))':d=$duration*30:s=1080x1920:fps=30",
                'pan_left' => "zoompan=z=1.2:x='iw/2-(iw/zoom/2)+((iw/zoom)*on/($duration*30))':d=$duration*30:s=1080x1920:fps=30",
                'pan_right' => "zoompan=z=1.2:x='iw/2-(iw/zoom/2)-((iw/zoom)*on/($duration*30))':d=$duration*30:s=1080x1920:fps=30",
                default => "zoompan=z='min(zoom+0.003\\,1.5)':d=$duration*30:s=1080x1920:fps=30",
            };

            if ($storageType === 'PUBLIC') {
                $inputPath = $file->storeAs($uploadDir, $inputName, 'public');
                $inputFullPath = storage_path('app/public/' . $inputPath);
                $outputFullPath = storage_path('app/public/' . $uploadDir . $outputName);

                $ffmpegCmd = sprintf(
                    'ffmpeg -i %s -vf "%s" -c:v libx264 -preset fast -pix_fmt yuv420p -t %d %s -y 2>&1',
                    escapeshellarg($inputFullPath),
                    $zoomExpr,
                    $duration,
                    escapeshellarg($outputFullPath)
                );

                exec($ffmpegCmd, $output, $returnCode);

                // Clean up input
                @unlink($inputFullPath);

                if ($returnCode !== 0) {
                    return response()->json(['status' => false, 'message' => 'Video generation from image failed']);
                }

                $videoUrl = $uploadDir . $outputName;

                return response()->json([
                    'status' => true,
                    'message' => 'Video generated successfully',
                    'data' => [
                        'video_url' => $videoUrl,
                        'duration' => $duration,
                    ],
                ]);
            } else {
                $tempInput = tempnam(sys_get_temp_dir(), 'img_in_');
                $tempOutput = tempnam(sys_get_temp_dir(), 'ai_vid_') . '.mp4';
                file_put_contents($tempInput, file_get_contents($file->getRealPath()));

                $ffmpegCmd = sprintf(
                    'ffmpeg -i %s -vf "%s" -c:v libx264 -preset fast -pix_fmt yuv420p -t %d %s -y 2>&1',
                    escapeshellarg($tempInput),
                    $zoomExpr,
                    $duration,
                    escapeshellarg($tempOutput)
                );

                exec($ffmpegCmd, $output, $returnCode);

                @unlink($tempInput);

                if ($returnCode !== 0) {
                    @unlink($tempOutput);
                    return response()->json(['status' => false, 'message' => 'Video generation from image failed']);
                }

                $disk = ($storageType === 'AWSS3') ? 's3' : 'do';
                $cloudPath = $uploadDir . $outputName;
                Storage::disk($disk)->put($cloudPath, file_get_contents($tempOutput), 'public');
                @unlink($tempOutput);

                $videoUrl = Storage::disk($disk)->url($cloudPath);

                return response()->json([
                    'status' => true,
                    'message' => 'Video generated successfully',
                    'data' => [
                        'video_url' => $videoUrl,
                        'duration' => $duration,
                    ],
                ]);
            }
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Video generation service unavailable']);
        }
    }
}
