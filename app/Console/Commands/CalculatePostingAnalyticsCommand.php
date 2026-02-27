<?php

namespace App\Console\Commands;

use App\Models\Constants;
use App\Models\Posts;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CalculatePostingAnalyticsCommand extends Command
{
    protected $signature = 'analytics:calculate-posting';
    protected $description = 'Calculate posting analytics (best time to post) for all active creators';

    public function handle(): int
    {
        $this->info('Calculating posting analytics...');

        // Get creators with at least 5 published posts in last 90 days
        $creatorIds = Posts::where('post_status', Constants::postStatusPublished)
            ->where('created_at', '>=', Carbon::now()->subDays(90))
            ->groupBy('user_id')
            ->havingRaw('COUNT(*) >= 5')
            ->pluck('user_id');

        $this->info("Found {$creatorIds->count()} eligible creators.");

        $processed = 0;
        foreach ($creatorIds as $userId) {
            try {
                $this->calculateForUser($userId);
                $processed++;
            } catch (\Exception $e) {
                $this->error("  Failed for user #{$userId}: {$e->getMessage()}");
                Log::error('Posting analytics calculation failed', [
                    'user_id' => $userId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Done. Processed {$processed} creators.");
        return 0;
    }

    private function calculateForUser(int $userId): void
    {
        $posts = Posts::where('user_id', $userId)
            ->where('post_status', Constants::postStatusPublished)
            ->where('created_at', '>=', Carbon::now()->subDays(90))
            ->select('views', 'likes', 'comments', 'shares', 'saves', 'created_at')
            ->get();

        if ($posts->isEmpty()) return;

        // Group by day_of_week + hour_of_day
        $grouped = $posts->groupBy(function ($post) {
            $dt = Carbon::parse($post->created_at);
            return $dt->dayOfWeek . '_' . $dt->hour;
        });

        foreach ($grouped as $key => $items) {
            [$day, $hour] = explode('_', $key);

            $avgViews = $items->avg('views');
            $avgLikes = $items->avg('likes');
            $totalEngagement = $items->avg(function ($p) {
                return $p->likes + $p->comments + $p->shares + $p->saves;
            });
            $avgViewsSafe = max($avgViews, 1);
            $engagementRate = ($totalEngagement / $avgViewsSafe) * 100;

            DB::table('tbl_posting_analytics')->updateOrInsert(
                [
                    'user_id' => $userId,
                    'hour_of_day' => (int) $hour,
                    'day_of_week' => (int) $day,
                ],
                [
                    'avg_views' => round($avgViews, 2),
                    'avg_likes' => round($avgLikes, 2),
                    'avg_engagement_rate' => round($engagementRate, 2),
                    'sample_count' => $items->count(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
