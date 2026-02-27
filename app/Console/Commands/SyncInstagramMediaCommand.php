<?php

namespace App\Console\Commands;

use App\Jobs\ImportInstagramVideoJob;
use App\Models\InstagramImports;
use App\Models\Users;
use App\Services\InstagramGraphService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncInstagramMediaCommand extends Command
{
    protected $signature = 'instagram:sync-media';
    protected $description = 'Sync new Instagram videos for users with auto-sync enabled';

    public function handle(): int
    {
        $users = Users::whereNotNull('instagram_user_id')
            ->where('instagram_auto_sync', true)
            ->where('instagram_token_expires_at', '>', now())
            ->get();

        $this->info("Found {$users->count()} users with Instagram auto-sync enabled");

        foreach ($users as $user) {
            $this->processUser($user);
        }

        // Also refresh tokens expiring within 7 days
        $this->refreshExpiringTokens();

        $this->info('Instagram sync completed');
        return 0;
    }

    private function processUser(Users $user): void
    {
        try {
            $result = InstagramGraphService::getUserMedia($user->instagram_access_token);

            $queued = 0;
            foreach ($result['data'] ?? [] as $media) {
                // Skip non-video media
                if (!in_array($media['media_type'] ?? '', ['VIDEO', 'REELS'])) {
                    continue;
                }

                // Skip if already imported
                $exists = InstagramImports::where('user_id', $user->id)
                    ->where('instagram_media_id', $media['id'])
                    ->exists();
                if ($exists) {
                    continue;
                }

                // Skip media older than last sync
                if ($user->instagram_last_sync_at) {
                    $mediaTime = Carbon::parse($media['timestamp']);
                    if ($mediaTime->lt(Carbon::parse($user->instagram_last_sync_at))) {
                        continue;
                    }
                }

                // Dispatch import job
                ImportInstagramVideoJob::dispatch(
                    $user->id,
                    $media['id'],
                    $media,
                    $media['caption'] ?? null
                );

                $queued++;
                $this->info("  Queued import for user {$user->id}: {$media['id']}");
            }

            // Update last sync timestamp
            $user->instagram_last_sync_at = now();
            $user->save();

            if ($queued > 0) {
                $this->info("  User {$user->id}: {$queued} new video(s) queued");
            }

        } catch (\Exception $e) {
            $this->error("Failed to sync for user {$user->id}: {$e->getMessage()}");
            Log::error('Instagram sync failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function refreshExpiringTokens(): void
    {
        $expiringUsers = Users::whereNotNull('instagram_user_id')
            ->whereNotNull('instagram_access_token')
            ->where('instagram_token_expires_at', '<=', now()->addDays(7))
            ->where('instagram_token_expires_at', '>', now())
            ->get();

        foreach ($expiringUsers as $user) {
            try {
                $result = InstagramGraphService::refreshLongLivedToken($user->instagram_access_token);
                $user->instagram_access_token = $result['access_token'];
                $user->instagram_token_expires_at = now()->addSeconds($result['expires_in'] ?? 5184000);
                $user->save();

                $this->info("  Refreshed token for user {$user->id}");
            } catch (\Exception $e) {
                $this->error("  Failed to refresh token for user {$user->id}: {$e->getMessage()}");
                Log::error('Instagram token refresh failed', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
