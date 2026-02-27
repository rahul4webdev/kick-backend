<?php

namespace App\Console\Commands;

use App\Models\Constants;
use App\Models\Posts;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class PublishScheduledPostsCommand extends Command
{
    protected $signature = 'posts:publish-scheduled';
    protected $description = 'Publish posts whose scheduled_at time has passed';

    public function handle(): int
    {
        $posts = Posts::where('post_status', Constants::postStatusScheduled)
            ->where('scheduled_at', '<=', now())
            ->get();

        if ($posts->isEmpty()) {
            return 0;
        }

        $this->info("Publishing {$posts->count()} scheduled post(s)...");

        foreach ($posts as $post) {
            try {
                $post->post_status = Constants::postStatusPublished;
                $post->save();

                $this->info("  Published post #{$post->id} (scheduled_at: {$post->scheduled_at})");
            } catch (\Exception $e) {
                $post->post_status = Constants::postStatusFailed;
                $post->save();

                $this->error("  Failed to publish post #{$post->id}: {$e->getMessage()}");
                Log::error('Scheduled post publish failed', [
                    'post_id' => $post->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info('Done.');
        return 0;
    }
}
