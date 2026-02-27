<?php

namespace App\Http\Controllers;

use App\Models\Constants;
use App\Models\GlobalFunction;
use App\Models\UserMilestone;
use Carbon\Carbon;
use Illuminate\Http\Request;

class MilestoneController extends Controller
{
    /**
     * Fetch all milestones for the current user.
     */
    public function fetchMyMilestones(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);

        $milestones = UserMilestone::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($m) {
                $types = UserMilestone::milestoneTypes();
                $typeInfo = $types[$m->type] ?? ['label' => $m->type, 'icon' => 'star'];
                return [
                    'id' => $m->id,
                    'type' => $m->type,
                    'label' => $typeInfo['label'],
                    'icon' => $typeInfo['icon'],
                    'data_id' => $m->data_id,
                    'metadata' => $m->metadata,
                    'is_seen' => $m->is_seen,
                    'is_shared' => $m->is_shared,
                    'created_at' => $m->created_at->toISOString(),
                ];
            });

        return GlobalFunction::sendDataResponse(true, 'Milestones fetched', $milestones);
    }

    /**
     * Check and award any new milestones the user has earned.
     * Called periodically or after key events.
     */
    public function checkMilestones(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);

        $newMilestones = [];

        // Follower milestones
        $followerThresholds = [
            'followers_100' => 100,
            'followers_1k' => 1000,
            'followers_10k' => 10000,
            'followers_100k' => 100000,
            'followers_1m' => 1000000,
        ];

        foreach ($followerThresholds as $type => $threshold) {
            if ($user->follower_count >= $threshold) {
                $milestone = $this->_awardMilestone($user->id, $type, null, [
                    'follower_count' => $user->follower_count,
                ]);
                if ($milestone) $newMilestones[] = $milestone;
            }
        }

        // First post milestone
        $postCount = \DB::table('tbl_post')->where('user_id', $user->id)->count();
        if ($postCount >= 1) {
            $milestone = $this->_awardMilestone($user->id, 'first_post', null, [
                'post_count' => $postCount,
            ]);
            if ($milestone) $newMilestones[] = $milestone;
        }

        // 100 posts milestone
        if ($postCount >= 100) {
            $milestone = $this->_awardMilestone($user->id, 'posts_100', null, [
                'post_count' => $postCount,
            ]);
            if ($milestone) $newMilestones[] = $milestone;
        }

        // Viral post (any post with > 10000 views)
        $viralPost = \DB::table('tbl_post')
            ->where('user_id', $user->id)
            ->where('views', '>=', 10000)
            ->orderBy('views', 'desc')
            ->first();

        if ($viralPost) {
            $milestone = $this->_awardMilestone($user->id, 'viral_post', $viralPost->id, [
                'views' => $viralPost->views,
                'likes' => $viralPost->likes,
            ]);
            if ($milestone) $newMilestones[] = $milestone;
        }

        // Anniversary (1 year on platform)
        if ($user->created_at && Carbon::parse($user->created_at)->addYear()->isPast()) {
            $milestone = $this->_awardMilestone($user->id, 'anniversary_1y', null, [
                'joined_at' => $user->created_at,
            ]);
            if ($milestone) $newMilestones[] = $milestone;
        }

        // Format response
        $types = UserMilestone::milestoneTypes();
        $formatted = collect($newMilestones)->map(function ($m) use ($types) {
            $typeInfo = $types[$m->type] ?? ['label' => $m->type, 'icon' => 'star'];
            return [
                'id' => $m->id,
                'type' => $m->type,
                'label' => $typeInfo['label'],
                'icon' => $typeInfo['icon'],
                'data_id' => $m->data_id,
                'metadata' => $m->metadata,
                'created_at' => $m->created_at->toISOString(),
            ];
        });

        return GlobalFunction::sendDataResponse(true, 'Milestones checked', [
            'new_milestones' => $formatted,
        ]);
    }

    /**
     * Mark a milestone as seen.
     * Params: milestone_id
     */
    public function markMilestoneSeen(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);

        $milestone = UserMilestone::where('id', $request->milestone_id)
            ->where('user_id', $user->id)
            ->first();

        if (!$milestone) {
            return GlobalFunction::sendSimpleResponse(false, 'Milestone not found');
        }

        $milestone->is_seen = true;
        $milestone->save();

        return GlobalFunction::sendSimpleResponse(true, 'Milestone marked as seen');
    }

    /**
     * Mark a milestone as shared.
     * Params: milestone_id
     */
    public function markMilestoneShared(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);

        $milestone = UserMilestone::where('id', $request->milestone_id)
            ->where('user_id', $user->id)
            ->first();

        if (!$milestone) {
            return GlobalFunction::sendSimpleResponse(false, 'Milestone not found');
        }

        $milestone->is_shared = true;
        $milestone->save();

        return GlobalFunction::sendSimpleResponse(true, 'Milestone marked as shared');
    }

    /**
     * Award a milestone if not already awarded.
     */
    private function _awardMilestone(int $userId, string $type, ?int $dataId, array $metadata): ?UserMilestone
    {
        $exists = UserMilestone::where('user_id', $userId)
            ->where('type', $type)
            ->exists();

        if ($exists) return null;

        return UserMilestone::create([
            'user_id' => $userId,
            'type' => $type,
            'data_id' => $dataId,
            'metadata' => $metadata,
        ]);
    }
}
