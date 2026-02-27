<?php

namespace App\Http\Controllers;

use App\Models\Constants;
use App\Models\GlobalFunction;
use App\Models\GlobalSettings;
use App\Models\Posts;
use App\Models\Users;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CreatorDashboardController extends Controller
{
    /**
     * Fetch creator's dashboard overview with key metrics.
     * Returns: total views, likes, comments, shares, follower count,
     * engagement rate, top posts, and time-series data for charts.
     */
    public function fetchCreatorDashboard(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if (!$user) {
            return GlobalFunction::sendSimpleResponse(false, 'User not found!');
        }
        if ($user->is_freez == 1) {
            return GlobalFunction::sendSimpleResponse(false, 'This user is frozen!');
        }

        $period = $request->period ?? '30d';
        $days = match ($period) {
            '7d' => 7,
            '30d' => 30,
            '90d' => 90,
            'all' => 365 * 5,
            default => 30,
        };
        $startDate = Carbon::now()->subDays($days);

        // 1. Overall stats (all-time)
        $allTimeStats = Posts::where('user_id', $user->id)
            ->selectRaw('
                COUNT(*) as total_posts,
                COALESCE(SUM(views), 0) as total_views,
                COALESCE(SUM(likes), 0) as total_likes,
                COALESCE(SUM(comments), 0) as total_comments,
                COALESCE(SUM(shares), 0) as total_shares,
                COALESCE(SUM(saves), 0) as total_saves
            ')
            ->first();

        // 2. Period stats (within selected date range)
        $periodStats = Posts::where('user_id', $user->id)
            ->where('created_at', '>=', $startDate)
            ->selectRaw('
                COUNT(*) as posts_count,
                COALESCE(SUM(views), 0) as views,
                COALESCE(SUM(likes), 0) as likes,
                COALESCE(SUM(comments), 0) as comments,
                COALESCE(SUM(shares), 0) as shares,
                COALESCE(SUM(saves), 0) as saves
            ')
            ->first();

        // 3. Engagement rate
        $totalViews = (int) $allTimeStats->total_views;
        $totalEngagement = (int) $allTimeStats->total_likes + (int) $allTimeStats->total_comments
            + (int) $allTimeStats->total_shares + (int) $allTimeStats->total_saves;
        $engagementRate = $totalViews > 0
            ? round(($totalEngagement / $totalViews) * 100, 2)
            : 0;

        // 4. Top 5 posts by engagement score
        $topPosts = Posts::where('user_id', $user->id)
            ->with(Constants::postsWithArray)
            ->orderByRaw('(views + likes * 3 + comments * 5 + shares * 5 + saves * 2) DESC')
            ->limit(5)
            ->get();
        $topPostsList = GlobalFunction::processPostsListData($topPosts, $user);

        // 5. Content breakdown by post type
        $contentBreakdown = Posts::where('user_id', $user->id)
            ->selectRaw('post_type, COUNT(*) as count, COALESCE(SUM(views), 0) as views, COALESCE(SUM(likes), 0) as likes')
            ->groupBy('post_type')
            ->get();

        // 6. Content type breakdown (normal, music video, trailer, news, short story)
        $contentTypeBreakdown = Posts::where('user_id', $user->id)
            ->selectRaw('content_type, COUNT(*) as count, COALESCE(SUM(views), 0) as views')
            ->groupBy('content_type')
            ->get();

        // 7. Daily views/likes chart data for the period
        $chartData = Posts::where('user_id', $user->id)
            ->where('created_at', '>=', $startDate)
            ->selectRaw("DATE(created_at) as date, COUNT(*) as posts, COALESCE(SUM(views), 0) as views, COALESCE(SUM(likes), 0) as likes")
            ->groupByRaw('DATE(created_at)')
            ->orderByRaw('DATE(created_at) ASC')
            ->get();

        // 8. Follower growth (new followers in period)
        $newFollowers = DB::table('tbl_followers')
            ->where('to_user_id', $user->id)
            ->where('created_at', '>=', $startDate)
            ->count();

        // 9. Follower growth chart (daily)
        $followerChart = DB::table('tbl_followers')
            ->where('to_user_id', $user->id)
            ->where('created_at', '>=', $startDate)
            ->selectRaw("DATE(created_at) as date, COUNT(*) as new_followers")
            ->groupByRaw('DATE(created_at)')
            ->orderByRaw('DATE(created_at) ASC')
            ->get();

        // 10. Ad revenue estimation based on eCPM
        $settings = GlobalSettings::getCached();
        $ecpmRate = (float) ($settings->ecpm_rate ?? 2.00);
        $revenueShare = (int) ($settings->creator_revenue_share ?? 55);

        $periodViews = (int) $periodStats->views;
        $estimatedTotalAdRevenue = round(($totalViews / 1000) * $ecpmRate * ($revenueShare / 100), 2);
        $estimatedPeriodAdRevenue = round(($periodViews / 1000) * $ecpmRate * ($revenueShare / 100), 2);

        $data = [
            'overview' => [
                'total_posts' => (int) $allTimeStats->total_posts,
                'total_views' => (int) $allTimeStats->total_views,
                'total_likes' => (int) $allTimeStats->total_likes,
                'total_comments' => (int) $allTimeStats->total_comments,
                'total_shares' => (int) $allTimeStats->total_shares,
                'total_saves' => (int) $allTimeStats->total_saves,
                'follower_count' => (int) ($user->follower_count ?? 0),
                'following_count' => (int) ($user->following_count ?? 0),
                'engagement_rate' => $engagementRate,
            ],
            'period' => [
                'label' => $period,
                'posts' => (int) $periodStats->posts_count,
                'views' => (int) $periodStats->views,
                'likes' => (int) $periodStats->likes,
                'comments' => (int) $periodStats->comments,
                'shares' => (int) $periodStats->shares,
                'saves' => (int) $periodStats->saves,
                'new_followers' => $newFollowers,
            ],
            'ad_revenue' => [
                'ecpm_rate' => $ecpmRate,
                'revenue_share_percent' => $revenueShare,
                'total_impressions' => $totalViews,
                'period_impressions' => $periodViews,
                'estimated_total_revenue' => $estimatedTotalAdRevenue,
                'estimated_period_revenue' => $estimatedPeriodAdRevenue,
            ],
            'top_posts' => $topPostsList,
            'content_breakdown' => $contentBreakdown,
            'content_type_breakdown' => $contentTypeBreakdown,
            'chart_data' => $chartData,
            'follower_chart' => $followerChart,
        ];

        return GlobalFunction::sendDataResponse(true, 'creator dashboard data', $data);
    }

    /**
     * Fetch detailed analytics for a specific post.
     */
    public function fetchPostAnalytics(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if (!$user) {
            return GlobalFunction::sendSimpleResponse(false, 'User not found!');
        }

        $rules = ['post_id' => 'required'];
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return GlobalFunction::sendSimpleResponse(false, $validator->errors()->first());
        }

        $post = Posts::where('id', $request->post_id)
            ->where('user_id', $user->id)
            ->with(Constants::postsWithArray)
            ->first();

        if (!$post) {
            return GlobalFunction::sendSimpleResponse(false, 'Post not found or not yours');
        }

        $processedPost = GlobalFunction::processPostsListData(collect([$post]), $user);

        // Get daily views for this post (from created_at)
        $createdAt = Carbon::parse($post->created_at);
        $daysSinceCreation = $createdAt->diffInDays(Carbon::now());
        $avgDailyViews = $daysSinceCreation > 0
            ? round($post->views / $daysSinceCreation, 1)
            : $post->views;

        // Engagement rate for this post
        $postViews = (int) $post->views;
        $postEngagement = (int) $post->likes + (int) $post->comments
            + (int) $post->shares + (int) $post->saves;
        $postEngagementRate = $postViews > 0
            ? round(($postEngagement / $postViews) * 100, 2)
            : 0;

        // Estimated ad revenue for this post
        $settings = GlobalSettings::getCached();
        $ecpmRate = (float) ($settings->ecpm_rate ?? 2.00);
        $revenueShare = (int) ($settings->creator_revenue_share ?? 55);
        $estimatedPostRevenue = round(($postViews / 1000) * $ecpmRate * ($revenueShare / 100), 2);

        $data = [
            'post' => $processedPost[0] ?? null,
            'analytics' => [
                'views' => (int) $post->views,
                'likes' => (int) $post->likes,
                'comments' => (int) $post->comments,
                'shares' => (int) $post->shares,
                'saves' => (int) $post->saves,
                'engagement_rate' => $postEngagementRate,
                'avg_daily_views' => $avgDailyViews,
                'days_since_creation' => $daysSinceCreation,
                'created_at' => $post->created_at,
                'estimated_revenue' => $estimatedPostRevenue,
            ],
        ];

        return GlobalFunction::sendDataResponse(true, 'post analytics', $data);
    }

    /**
     * Fetch audience insights for the creator.
     * Returns: follower demographics, most active times, top giftors.
     */
    public function fetchAudienceInsights(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if (!$user) {
            return GlobalFunction::sendSimpleResponse(false, 'User not found!');
        }

        // Top 10 followers by their follower_count (most influential followers)
        $topFollowers = Users::whereIn('id', function ($q) use ($user) {
                $q->select('from_user_id')
                    ->from('tbl_followers')
                    ->where('to_user_id', $user->id);
            })
            ->where('is_freez', 0)
            ->orderBy('follower_count', 'DESC')
            ->select(explode(',', Constants::userPublicFields))
            ->limit(10)
            ->get();

        // Top gifters (users who sent most coins)
        $topGifters = DB::table('tbl_coin_transactions')
            ->where('user_id', $user->id)
            ->where('type', Constants::txnGiftReceived)
            ->where('direction', Constants::credit)
            ->whereNotNull('related_user_id')
            ->selectRaw('related_user_id, SUM(coins) as total_coins, COUNT(*) as gift_count')
            ->groupBy('related_user_id')
            ->orderByRaw('SUM(coins) DESC')
            ->limit(10)
            ->get();

        // Enrich gifters with user data
        $gifterIds = $topGifters->pluck('related_user_id')->toArray();
        $gifterUsers = Users::whereIn('id', $gifterIds)
            ->select(explode(',', Constants::userPublicFields))
            ->get()
            ->keyBy('id');

        $enrichedGifters = $topGifters->map(function ($g) use ($gifterUsers) {
            $u = $gifterUsers[$g->related_user_id] ?? null;
            return [
                'user' => $u,
                'total_coins' => (int) $g->total_coins,
                'gift_count' => (int) $g->gift_count,
            ];
        })->filter(fn($g) => $g['user'] !== null)->values();

        // Recent follower activity (last 30 days, grouped by day)
        $followerActivity = DB::table('tbl_followers')
            ->where('to_user_id', $user->id)
            ->where('created_at', '>=', Carbon::now()->subDays(30))
            ->selectRaw("DATE(created_at) as date, COUNT(*) as count")
            ->groupByRaw('DATE(created_at)')
            ->orderByRaw('DATE(created_at) ASC')
            ->get();

        $data = [
            'top_followers' => $topFollowers,
            'top_gifters' => $enrichedGifters,
            'follower_activity' => $followerActivity,
            'total_followers' => (int) ($user->follower_count ?? 0),
            'total_following' => (int) ($user->following_count ?? 0),
        ];

        return GlobalFunction::sendDataResponse(true, 'audience insights', $data);
    }

    /**
     * Fetch search insights for creators.
     * Shows what people are searching for across the platform
     * to help creators create relevant content.
     */
    public function fetchSearchInsights(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if (!$user) {
            return GlobalFunction::sendSimpleResponse(false, 'User not found!');
        }

        $period = $request->period ?? '7d';
        $days = match ($period) {
            '7d' => 7,
            '30d' => 30,
            '90d' => 90,
            default => 7,
        };
        $startDate = Carbon::now()->subDays($days);

        // 1. Top trending search terms (platform-wide, anonymized)
        $trendingSearches = DB::table('tbl_search_history')
            ->where('created_at', '>=', $startDate)
            ->selectRaw("LOWER(TRIM(keyword)) as term, COUNT(*) as search_count, COUNT(DISTINCT user_id) as unique_users, ROUND(AVG(result_count)) as avg_results")
            ->groupByRaw('LOWER(TRIM(keyword))')
            ->havingRaw('COUNT(*) >= 2')
            ->orderByRaw('COUNT(*) DESC')
            ->limit(30)
            ->get();

        // 2. Search volume over time (daily)
        $searchVolume = DB::table('tbl_search_history')
            ->where('created_at', '>=', $startDate)
            ->selectRaw("DATE(created_at) as date, COUNT(*) as searches, COUNT(DISTINCT user_id) as unique_searchers")
            ->groupByRaw('DATE(created_at)')
            ->orderByRaw('DATE(created_at) ASC')
            ->get();

        // 3. Rising searches (terms that appeared more in last 3 days vs prior)
        $recentDate = Carbon::now()->subDays(3);
        $risingSearches = DB::table('tbl_search_history')
            ->where('created_at', '>=', $startDate)
            ->selectRaw("
                LOWER(TRIM(keyword)) as term,
                COUNT(*) FILTER (WHERE created_at >= ?) as recent_count,
                COUNT(*) FILTER (WHERE created_at < ?) as older_count,
                COUNT(*) as total_count
            ", [$recentDate, $recentDate])
            ->groupByRaw('LOWER(TRIM(keyword))')
            ->havingRaw('COUNT(*) FILTER (WHERE created_at >= ?) >= 2', [$recentDate])
            ->orderByRaw('COUNT(*) FILTER (WHERE created_at >= ?) DESC', [$recentDate])
            ->limit(10)
            ->get();

        // 4. Low-result searches (opportunity keywords - what users search but find few results)
        $lowResultSearches = DB::table('tbl_search_history')
            ->where('created_at', '>=', $startDate)
            ->selectRaw("LOWER(TRIM(keyword)) as term, COUNT(*) as search_count, ROUND(AVG(result_count)) as avg_results")
            ->groupByRaw('LOWER(TRIM(keyword))')
            ->havingRaw('COUNT(*) >= 3 AND AVG(result_count) < 5')
            ->orderByRaw('COUNT(*) DESC')
            ->limit(10)
            ->get();

        // 5. Total stats
        $totalSearches = DB::table('tbl_search_history')
            ->where('created_at', '>=', $startDate)
            ->count();
        $uniqueSearchers = DB::table('tbl_search_history')
            ->where('created_at', '>=', $startDate)
            ->distinct('user_id')
            ->count('user_id');

        $data = [
            'period' => $period,
            'total_searches' => $totalSearches,
            'unique_searchers' => $uniqueSearchers,
            'trending_searches' => $trendingSearches,
            'search_volume' => $searchVolume,
            'rising_searches' => $risingSearches,
            'low_result_searches' => $lowResultSearches,
        ];

        return GlobalFunction::sendDataResponse(true, 'search insights', $data);
    }
}
