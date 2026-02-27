<?php

namespace App\Jobs;

use App\Models\Constants;
use App\Models\GlobalFunction;
use App\Models\InstagramImports;
use App\Models\Users;
use App\Services\InstagramGraphService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ImportInstagramVideoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 600;

    private int $userId;
    private string $instagramMediaId;
    private array $mediaData;
    private ?string $caption;

    public function __construct(int $userId, string $instagramMediaId, array $mediaData, ?string $caption)
    {
        $this->userId = $userId;
        $this->instagramMediaId = $instagramMediaId;
        $this->mediaData = $mediaData;
        $this->caption = $caption;
        $this->onQueue('default');
    }

    public function handle(): void
    {
        // 1. Create or update tracking record
        $import = InstagramImports::firstOrNew([
            'user_id' => $this->userId,
            'instagram_media_id' => $this->instagramMediaId,
        ]);
        $import->media_type = $this->mediaData['media_type'] ?? 'VIDEO';
        $import->status = 'processing';
        $import->error_message = null;
        $import->save();

        $videoTempPath = null;
        $thumbTempPath = null;

        try {
            $user = Users::find($this->userId);
            if (!$user) {
                throw new \Exception("User {$this->userId} not found");
            }

            // 2. Download video from Instagram CDN
            $mediaUrl = $this->mediaData['media_url'] ?? null;
            if (!$mediaUrl) {
                throw new \Exception('No media_url in media data');
            }
            $videoTempPath = InstagramGraphService::downloadVideo($mediaUrl);

            // 3. Detect aspect ratio to determine post type
            $postType = $this->determinePostType($videoTempPath);

            // 4. Get or generate thumbnail
            $thumbTempPath = $this->getThumbnail($videoTempPath);

            // 5. Upload video to configured storage (S3/DO/Local)
            $videoFile = new UploadedFile($videoTempPath, 'instagram_video.mp4', 'video/mp4', null, true);
            $videoPath = GlobalFunction::saveFileAndGivePath($videoFile);

            // 6. Upload thumbnail
            $thumbFile = new UploadedFile($thumbTempPath, 'instagram_thumb.jpg', 'image/jpeg', null, true);
            $thumbPath = GlobalFunction::saveFileAndGivePath($thumbFile);

            // 7. Build metadata
            $metadata = json_encode([
                'source' => 'instagram',
                'instagram_media_id' => $this->instagramMediaId,
                'instagram_permalink' => $this->mediaData['permalink'] ?? null,
                'imported_at' => now()->toIso8601String(),
            ]);

            // 8. Create post using generatePost
            $request = new Request([
                'can_comment' => 1,
                'visibility' => Constants::postVisibilityPublic,
                'description' => $this->caption ?? '',
                'video' => $videoPath,
                'thumbnail' => $thumbPath,
                'metadata' => $metadata,
            ]);

            $post = GlobalFunction::generatePost($request, $postType, $user, null);

            // 9. Update tracking record
            $import->post_id = $post->id;
            $import->status = 'completed';
            $import->imported_at = now();
            $import->save();

            Log::info('Instagram video imported', [
                'user_id' => $this->userId,
                'instagram_media_id' => $this->instagramMediaId,
                'post_id' => $post->id,
                'post_type' => $postType == Constants::postTypeReel ? 'reel' : 'video',
            ]);

        } catch (\Exception $e) {
            $import->status = 'failed';
            $import->error_message = substr($e->getMessage(), 0, 1000);
            $import->save();

            Log::error('ImportInstagramVideoJob failed', [
                'user_id' => $this->userId,
                'instagram_media_id' => $this->instagramMediaId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        } finally {
            // 10. Cleanup temp files
            if ($videoTempPath && file_exists($videoTempPath)) {
                @unlink($videoTempPath);
            }
            if ($thumbTempPath && file_exists($thumbTempPath)) {
                @unlink($thumbTempPath);
            }
        }
    }

    /**
     * Determine post type by video aspect ratio.
     * Vertical (height > width) → Reel, Horizontal/Square → Video
     */
    private function determinePostType(string $videoPath): int
    {
        // If Instagram says it's a Reel, trust that
        if (($this->mediaData['media_type'] ?? '') === 'REELS') {
            return Constants::postTypeReel;
        }

        // Try FFprobe for actual dimensions
        try {
            $output = [];
            $cmd = sprintf(
                'ffprobe -v error -select_streams v:0 -show_entries stream=width,height -of csv=s=x:p=0 %s 2>&1',
                escapeshellarg($videoPath)
            );
            exec($cmd, $output, $returnCode);

            if ($returnCode === 0 && isset($output[0])) {
                $parts = explode('x', trim($output[0]));
                if (count($parts) === 2) {
                    $width = (int) $parts[0];
                    $height = (int) $parts[1];

                    if ($height > $width) {
                        return Constants::postTypeReel;
                    }
                    return Constants::postTypeVideo;
                }
            }
        } catch (\Exception $e) {
            Log::warning('FFprobe failed, falling back to media_type', ['error' => $e->getMessage()]);
        }

        // Fallback: default to reel (most IG videos are vertical)
        return Constants::postTypeReel;
    }

    /**
     * Get thumbnail: try FFmpeg extraction, fall back to Instagram thumbnail_url.
     */
    private function getThumbnail(string $videoPath): string
    {
        // Try FFmpeg first frame extraction
        try {
            $thumbPath = tempnam(sys_get_temp_dir(), 'ig_thumb_') . '.jpg';
            $cmd = sprintf(
                'ffmpeg -i %s -vframes 1 -q:v 2 -y %s 2>&1',
                escapeshellarg($videoPath),
                escapeshellarg($thumbPath)
            );
            exec($cmd, $output, $returnCode);

            if ($returnCode === 0 && file_exists($thumbPath) && filesize($thumbPath) > 0) {
                return $thumbPath;
            }
            @unlink($thumbPath);
        } catch (\Exception $e) {
            Log::warning('FFmpeg thumbnail extraction failed', ['error' => $e->getMessage()]);
        }

        // Fallback: download thumbnail from Instagram
        $thumbnailUrl = $this->mediaData['thumbnail_url'] ?? null;
        if ($thumbnailUrl) {
            return InstagramGraphService::downloadThumbnail($thumbnailUrl);
        }

        // Last resort: use video file as both (will look odd but won't break)
        throw new \Exception('Could not generate or download thumbnail');
    }

    public function failed(\Throwable $exception): void
    {
        $import = InstagramImports::where('user_id', $this->userId)
            ->where('instagram_media_id', $this->instagramMediaId)
            ->first();

        if ($import) {
            $import->status = 'failed';
            $import->error_message = substr($exception->getMessage(), 0, 1000);
            $import->save();
        }

        Log::error('ImportInstagramVideoJob permanently failed', [
            'user_id' => $this->userId,
            'instagram_media_id' => $this->instagramMediaId,
            'error' => $exception->getMessage(),
        ]);
    }
}
