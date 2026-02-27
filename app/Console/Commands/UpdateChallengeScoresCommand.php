<?php

namespace App\Console\Commands;

use App\Models\Constants;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdateChallengeScoresCommand extends Command
{
    protected $signature = 'challenges:update-scores';
    protected $description = 'Recalculate challenge entry scores based on post engagement';

    public function handle(): int
    {
        $this->info('Updating challenge scores...');

        $activeChallenges = DB::table('tbl_challenges')
            ->where('status', Constants::challengeStatusActive)
            ->where('is_active', true)
            ->pluck('id');

        if ($activeChallenges->isEmpty()) {
            $this->info('No active challenges found.');
            return 0;
        }

        $updated = 0;

        foreach ($activeChallenges as $challengeId) {
            try {
                $entries = DB::table('tbl_challenge_entries')
                    ->where('challenge_id', $challengeId)
                    ->get();

                foreach ($entries as $entry) {
                    $post = DB::table('tbl_post')->find($entry->post_id);
                    if (!$post) continue;

                    // Score = views + (likes * 2) + (shares * 3)
                    $score = ($post->views ?? 0) + (($post->likes ?? 0) * 2) + (($post->shares ?? 0) * 3);

                    DB::table('tbl_challenge_entries')
                        ->where('id', $entry->id)
                        ->update(['score' => $score]);

                    $updated++;
                }

                // Auto-end challenges past their end date
                $challenge = DB::table('tbl_challenges')->find($challengeId);
                if ($challenge && now()->gt($challenge->ends_at)) {
                    DB::table('tbl_challenges')
                        ->where('id', $challengeId)
                        ->update(['status' => Constants::challengeStatusEnded, 'updated_at' => now()]);
                    $this->info("  Challenge #{$challengeId} auto-ended (past end date).");
                }
            } catch (\Exception $e) {
                $this->error("  Failed for challenge #{$challengeId}: {$e->getMessage()}");
                Log::error('Challenge score update failed', [
                    'challenge_id' => $challengeId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Done. Updated {$updated} entry scores.");
        return 0;
    }
}
