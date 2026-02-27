<?php

namespace App\Http\Controllers;

use App\Models\Constants;
use App\Models\GlobalFunction;
use App\Models\Posts;
use App\Models\Users;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ChallengeController extends Controller
{
    /**
     * Create a new challenge.
     */
    public function createChallenge(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if (!$user) {
            return GlobalFunction::sendSimpleResponse(false, 'User not found!');
        }

        $rules = [
            'title' => 'required|max:200',
            'description' => 'required',
            'hashtag' => 'required|max:100',
            'starts_at' => 'required|date',
            'ends_at' => 'required|date|after:starts_at',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return GlobalFunction::sendSimpleResponse(false, $validator->errors()->first());
        }

        $hashtag = ltrim($request->hashtag, '#');

        $challenge = DB::table('tbl_challenges')->insertGetId([
            'creator_id' => $user->id,
            'title' => $request->title,
            'description' => $request->description,
            'hashtag' => $hashtag,
            'rules' => $request->rules,
            'challenge_type' => $request->challenge_type ?? 0,
            'cover_image' => $request->cover_image,
            'preview_video' => $request->preview_video,
            'starts_at' => $request->starts_at,
            'ends_at' => $request->ends_at,
            'prize_type' => $request->prize_type ?? 0,
            'prize_amount' => $request->prize_amount ?? 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $data = DB::table('tbl_challenges')->find($challenge);

        return GlobalFunction::sendDataResponse(true, 'Challenge created', $data);
    }

    /**
     * Fetch active/featured challenges.
     */
    public function fetchChallenges(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if (!$user) {
            return GlobalFunction::sendSimpleResponse(false, 'User not found!');
        }

        $query = DB::table('tbl_challenges')
            ->where('is_active', true)
            ->orderByDesc('is_featured')
            ->orderByDesc('entry_count');

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->last_item_id) {
            $query->where('id', '<', $request->last_item_id);
        }

        $challenges = $query->limit($request->limit ?? 20)->get();

        // Attach creator info
        foreach ($challenges as $challenge) {
            $challenge->creator = Users::select(explode(',', Constants::userPublicFields))
                ->find($challenge->creator_id);
            $challenge->has_entered = DB::table('tbl_challenge_entries')
                ->where('challenge_id', $challenge->id)
                ->where('user_id', $user->id)
                ->exists();
        }

        return GlobalFunction::sendDataResponse(true, 'Challenges fetched', $challenges);
    }

    /**
     * Fetch a single challenge by ID.
     */
    public function fetchChallengeById(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if (!$user) {
            return GlobalFunction::sendSimpleResponse(false, 'User not found!');
        }

        $challenge = DB::table('tbl_challenges')->find($request->challenge_id);
        if (!$challenge) {
            return GlobalFunction::sendSimpleResponse(false, 'Challenge not found');
        }

        $challenge->creator = Users::select(explode(',', Constants::userPublicFields))
            ->find($challenge->creator_id);
        $challenge->has_entered = DB::table('tbl_challenge_entries')
            ->where('challenge_id', $challenge->id)
            ->where('user_id', $user->id)
            ->exists();

        // Increment view count
        DB::table('tbl_challenges')->where('id', $challenge->id)->increment('view_count');

        return GlobalFunction::sendDataResponse(true, 'Challenge fetched', $challenge);
    }

    /**
     * Enter a challenge with a post.
     */
    public function enterChallenge(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if (!$user) {
            return GlobalFunction::sendSimpleResponse(false, 'User not found!');
        }

        $rules = [
            'challenge_id' => 'required',
            'post_id' => 'required|exists:tbl_post,id',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return GlobalFunction::sendSimpleResponse(false, $validator->errors()->first());
        }

        $challenge = DB::table('tbl_challenges')->find($request->challenge_id);
        if (!$challenge || $challenge->status != Constants::challengeStatusActive) {
            return GlobalFunction::sendSimpleResponse(false, 'Challenge is not active');
        }

        if (now()->gt($challenge->ends_at)) {
            return GlobalFunction::sendSimpleResponse(false, 'Challenge has ended');
        }

        // Check post belongs to user
        $post = Posts::find($request->post_id);
        if ($post->user_id != $user->id) {
            return GlobalFunction::sendSimpleResponse(false, 'Post does not belong to you');
        }

        // Check if already entered with this post
        $exists = DB::table('tbl_challenge_entries')
            ->where('challenge_id', $request->challenge_id)
            ->where('post_id', $request->post_id)
            ->exists();

        if ($exists) {
            return GlobalFunction::sendSimpleResponse(false, 'This post is already entered');
        }

        DB::table('tbl_challenge_entries')->insert([
            'challenge_id' => $request->challenge_id,
            'post_id' => $request->post_id,
            'user_id' => $user->id,
            'score' => 0,
            'created_at' => now(),
        ]);

        // Increment entry count
        DB::table('tbl_challenges')->where('id', $request->challenge_id)->increment('entry_count');

        // Notify challenge creator
        GlobalFunction::insertUserNotification(
            Constants::notify_challenge_entry,
            $user->id,
            $challenge->creator_id,
            $request->post_id
        );

        return GlobalFunction::sendSimpleResponse(true, 'Entered challenge successfully');
    }

    /**
     * Fetch entries for a challenge.
     */
    public function fetchEntries(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if (!$user) {
            return GlobalFunction::sendSimpleResponse(false, 'User not found!');
        }

        $entries = DB::table('tbl_challenge_entries')
            ->where('challenge_id', $request->challenge_id)
            ->orderByDesc('score')
            ->limit($request->limit ?? 20)
            ->get();

        foreach ($entries as $entry) {
            $entry->user = Users::select(explode(',', Constants::userPublicFields))
                ->find($entry->user_id);
            $post = Posts::with(Constants::postsWithArray)->find($entry->post_id);
            $entry->post = $post ? GlobalFunction::processPostsListData(collect([$post]), $user)[0] ?? null : null;
        }

        return GlobalFunction::sendDataResponse(true, 'Entries fetched', $entries);
    }

    /**
     * Fetch leaderboard for a challenge (top entries by score).
     */
    public function fetchLeaderboard(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if (!$user) {
            return GlobalFunction::sendSimpleResponse(false, 'User not found!');
        }

        $entries = DB::table('tbl_challenge_entries')
            ->where('challenge_id', $request->challenge_id)
            ->orderByDesc('score')
            ->limit($request->limit ?? 50)
            ->get();

        $rank = 1;
        foreach ($entries as $entry) {
            $entry->rank = $rank++;
            $entry->user = Users::select(explode(',', Constants::userPublicFields))
                ->find($entry->user_id);
        }

        return GlobalFunction::sendDataResponse(true, 'Leaderboard fetched', $entries);
    }

    /**
     * End a challenge (creator only).
     */
    public function endChallenge(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if (!$user) {
            return GlobalFunction::sendSimpleResponse(false, 'User not found!');
        }

        $challenge = DB::table('tbl_challenges')->find($request->challenge_id);
        if (!$challenge || $challenge->creator_id != $user->id) {
            return GlobalFunction::sendSimpleResponse(false, 'Unauthorized');
        }

        DB::table('tbl_challenges')
            ->where('id', $request->challenge_id)
            ->update(['status' => Constants::challengeStatusJudging, 'updated_at' => now()]);

        return GlobalFunction::sendSimpleResponse(true, 'Challenge moved to judging');
    }

    /**
     * Award prizes to top entries (creator only).
     */
    public function awardPrizes(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if (!$user) {
            return GlobalFunction::sendSimpleResponse(false, 'User not found!');
        }

        $challenge = DB::table('tbl_challenges')->find($request->challenge_id);
        if (!$challenge || $challenge->creator_id != $user->id) {
            return GlobalFunction::sendSimpleResponse(false, 'Unauthorized');
        }

        // Get top 3 entries
        $topEntries = DB::table('tbl_challenge_entries')
            ->where('challenge_id', $request->challenge_id)
            ->orderByDesc('score')
            ->limit(3)
            ->get();

        if ($topEntries->isEmpty()) {
            return GlobalFunction::sendSimpleResponse(false, 'No entries to award');
        }

        $prizeDistribution = [1.0, 0.5, 0.25]; // 100%, 50%, 25% of prize

        foreach ($topEntries as $index => $entry) {
            $prizeShare = $prizeDistribution[$index] ?? 0;
            $prizeCoins = (int) round($challenge->prize_amount * $prizeShare);

            if ($prizeCoins > 0 && $challenge->prize_type == 1) {
                // Credit coins to winner
                DB::table('tbl_users')->where('id', $entry->user_id)
                    ->increment('coin_wallet', $prizeCoins);

                DB::table('tbl_coin_transactions')->insert([
                    'user_id' => $entry->user_id,
                    'type' => Constants::coinTransactionChallengeReward,
                    'coins' => $prizeCoins,
                    'direction' => 1,
                    'reference_id' => $challenge->id,
                    'note' => "Challenge prize: #{$challenge->hashtag} (Rank " . ($index + 1) . ")",
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Mark as winner
            DB::table('tbl_challenge_entries')
                ->where('id', $entry->id)
                ->update(['is_winner' => true, 'rank' => $index + 1]);

            // Notify winner
            GlobalFunction::insertUserNotification(
                Constants::notify_challenge_winner,
                $challenge->creator_id,
                $entry->user_id,
                $entry->post_id
            );
        }

        // Mark challenge as completed
        DB::table('tbl_challenges')
            ->where('id', $request->challenge_id)
            ->update(['status' => Constants::challengeStatusCompleted, 'updated_at' => now()]);

        return GlobalFunction::sendSimpleResponse(true, 'Prizes awarded and challenge completed');
    }
}
