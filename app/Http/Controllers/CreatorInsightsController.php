<?php

namespace App\Http\Controllers;

use App\Models\Constants;
use App\Models\GlobalFunction;
use App\Models\GlobalSettings;
use App\Models\Posts;
use App\Services\GeminiService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CreatorInsightsController extends Controller
{
    /**
     * Generate AI insights for a creator based on their recent performance.
     */
    public function generateInsights(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if (!$user) {
            return GlobalFunction::sendSimpleResponse(false, 'User not found!');
        }

        $settings = GlobalSettings::getCached();
        if (empty($settings->ai_api_key)) {
            return GlobalFunction::sendSimpleResponse(false, 'AI service not configured');
        }

        // Gather creator performance data
        $performanceData = $this->gatherPerformanceData($user->id);

        if ($performanceData['total_posts'] < 3) {
            return GlobalFunction::sendSimpleResponse(false, 'Need at least 3 posts to generate insights');
        }

        $systemPrompt = "You are an expert social media growth strategist. Analyze the creator's performance data and provide actionable insights. Return a JSON array of 3-5 insights, each with: type (one of: growth, content, engagement, timing, audience), title (max 60 chars), body (max 200 chars, actionable advice), priority (1-5, 5 being most important).";

        $userMessage = "Here is the creator's performance data from the last 30 days:\n" . json_encode($performanceData, JSON_PRETTY_PRINT);

        try {
            $result = GeminiService::generateContent($systemPrompt, [
                ['role' => 'user', 'content' => $userMessage],
            ], 1024);

            if (!$result['success']) {
                return GlobalFunction::sendSimpleResponse(false, $result['error'] ?? 'AI service error');
            }

            $content = $result['text'] ?? '';

            // Parse JSON from response
            $parsed = json_decode($content, true);
            if (!$parsed) {
                // Try extracting from code blocks
                if (preg_match('/```(?:json)?\s*([\s\S]*?)\s*```/', $content, $matches)) {
                    $parsed = json_decode($matches[1], true);
                }
            }

            if (!$parsed || !is_array($parsed)) {
                return GlobalFunction::sendSimpleResponse(false, 'Failed to parse AI response');
            }

            // Store insights
            $expiresAt = Carbon::now()->addDays(7);
            $insertedIds = [];

            foreach ($parsed as $insight) {
                $id = DB::table('tbl_creator_ai_insights')->insertGetId([
                    'user_id' => $user->id,
                    'insight_type' => $insight['type'] ?? 'general',
                    'title' => $insight['title'] ?? 'Insight',
                    'body' => $insight['body'] ?? '',
                    'data' => json_encode($insight),
                    'is_read' => false,
                    'generated_at' => now(),
                    'expires_at' => $expiresAt,
                ]);
                $insertedIds[] = $id;
            }

            $insights = DB::table('tbl_creator_ai_insights')
                ->whereIn('id', $insertedIds)
                ->orderByDesc('generated_at')
                ->get();

            return GlobalFunction::sendDataResponse(true, 'Insights generated', $insights);

        } catch (\Exception $e) {
            Log::error('Creator insights generation failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            return GlobalFunction::sendSimpleResponse(false, 'AI service unavailable');
        }
    }

    /**
     * Fetch existing insights for the creator.
     */
    public function fetchInsights(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if (!$user) {
            return GlobalFunction::sendSimpleResponse(false, 'User not found!');
        }

        $query = DB::table('tbl_creator_ai_insights')
            ->where('user_id', $user->id)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            })
            ->orderByDesc('generated_at');

        if ($request->last_item_id) {
            $query->where('id', '<', $request->last_item_id);
        }

        $insights = $query->limit($request->limit ?? 20)->get();

        // Decode data JSON
        foreach ($insights as $insight) {
            $insight->data = json_decode($insight->data);
        }

        $unreadCount = DB::table('tbl_creator_ai_insights')
            ->where('user_id', $user->id)
            ->where('is_read', false)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            })
            ->count();

        return response()->json([
            'status' => true,
            'message' => 'Insights fetched',
            'data' => $insights,
            'unread_count' => $unreadCount,
        ]);
    }

    /**
     * Mark an insight as read.
     */
    public function markInsightRead(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if (!$user) {
            return GlobalFunction::sendSimpleResponse(false, 'User not found!');
        }

        if ($request->insight_id) {
            DB::table('tbl_creator_ai_insights')
                ->where('id', $request->insight_id)
                ->where('user_id', $user->id)
                ->update(['is_read' => true]);
        } else {
            // Mark all as read
            DB::table('tbl_creator_ai_insights')
                ->where('user_id', $user->id)
                ->where('is_read', false)
                ->update(['is_read' => true]);
        }

        return GlobalFunction::sendSimpleResponse(true, 'Marked as read');
    }

    /**
     * Fetch trending topics relevant to the creator.
     */
    public function fetchTrendingTopics(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if (!$user) {
            return GlobalFunction::sendSimpleResponse(false, 'User not found!');
        }

        $trendingHashtags = DB::table('tbl_hashtag')
            ->orderByDesc('hashtag_count')
            ->limit(20)
            ->get();

        $trendingSounds = DB::table('tbl_musics')
            ->orderByDesc('use_count')
            ->limit(10)
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'Trending topics fetched',
            'data' => [
                'hashtags' => $trendingHashtags,
                'sounds' => $trendingSounds,
            ],
        ]);
    }

    /**
     * Gather performance data for AI analysis.
     */
    private function gatherPerformanceData(int $userId): array
    {
        $since = Carbon::now()->subDays(30);

        $posts = Posts::where('user_id', $userId)
            ->where('post_status', Constants::postStatusPublished)
            ->where('created_at', '>=', $since)
            ->select('views', 'likes', 'comments', 'shares', 'saves', 'post_type', 'content_type', 'created_at')
            ->get();

        $totalPosts = $posts->count();
        $totalViews = $posts->sum('views');
        $totalLikes = $posts->sum('likes');
        $totalComments = $posts->sum('comments');
        $totalShares = $posts->sum('shares');

        $engagementRate = $totalViews > 0
            ? round(($totalLikes + $totalComments + $totalShares) / $totalViews * 100, 2)
            : 0;

        // Best performing post type
        $byType = $posts->groupBy('post_type')->map(fn($group) => [
            'count' => $group->count(),
            'avg_views' => round($group->avg('views')),
            'avg_likes' => round($group->avg('likes')),
        ]);

        // Posting frequency by day of week
        $byDay = $posts->groupBy(fn($p) => Carbon::parse($p->created_at)->dayOfWeek)
            ->map(fn($group) => $group->count());

        // Best posting hours
        $byHour = $posts->groupBy(fn($p) => Carbon::parse($p->created_at)->hour)
            ->map(fn($group) => [
                'count' => $group->count(),
                'avg_views' => round($group->avg('views')),
            ]);

        // Follower count
        $followerCount = DB::table('tbl_followers')
            ->where('to_user_id', $userId)
            ->count();

        $newFollowers = DB::table('tbl_followers')
            ->where('to_user_id', $userId)
            ->where('created_at', '>=', $since)
            ->count();

        return [
            'total_posts' => $totalPosts,
            'total_views' => $totalViews,
            'total_likes' => $totalLikes,
            'total_comments' => $totalComments,
            'total_shares' => $totalShares,
            'engagement_rate' => $engagementRate,
            'follower_count' => $followerCount,
            'new_followers_30d' => $newFollowers,
            'performance_by_type' => $byType,
            'posting_by_day' => $byDay,
            'posting_by_hour' => $byHour,
        ];
    }
}
