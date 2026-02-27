<?php

namespace App\Http\Controllers;

use App\Models\Constants;
use App\Models\GlobalFunction;
use App\Models\Posts;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ContentCalendarController extends Controller
{
    /**
     * Fetch calendar events (published, scheduled, drafts) for a given month.
     * Returns posts grouped by date for calendar rendering.
     */
    public function fetchCalendarEvents(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if (!$user) {
            return GlobalFunction::sendSimpleResponse(false, 'User not found!');
        }
        if ($user->is_freez == 1) {
            return GlobalFunction::sendSimpleResponse(false, 'This user is frozen!');
        }

        $year = $request->year ?? Carbon::now()->year;
        $month = $request->month ?? Carbon::now()->month;

        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        // Published posts in this month
        $publishedPosts = Posts::where('user_id', $user->id)
            ->where('post_status', Constants::postStatusPublished)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select('id', 'description', 'post_type', 'thumbnail', 'views', 'likes', 'comments', 'created_at')
            ->orderBy('created_at', 'ASC')
            ->get()
            ->map(function ($post) {
                $post->calendar_status = 'published';
                $post->calendar_date = Carbon::parse($post->created_at)->toDateString();
                return $post;
            });

        // Scheduled posts in this month
        $scheduledPosts = Posts::where('user_id', $user->id)
            ->where('post_status', Constants::postStatusScheduled)
            ->whereBetween('scheduled_at', [$startDate, $endDate])
            ->select('id', 'description', 'post_type', 'thumbnail', 'scheduled_at', 'created_at')
            ->orderBy('scheduled_at', 'ASC')
            ->get()
            ->map(function ($post) {
                $post->calendar_status = 'scheduled';
                $post->calendar_date = Carbon::parse($post->scheduled_at)->toDateString();
                return $post;
            });

        // Posts with draft_date set for this month (server-side draft calendar placement)
        $draftDatePosts = Posts::where('user_id', $user->id)
            ->whereNotNull('draft_date')
            ->whereBetween('draft_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->where('post_status', '!=', Constants::postStatusScheduled) // exclude already scheduled
            ->select('id', 'description', 'post_type', 'thumbnail', 'draft_date', 'created_at')
            ->orderBy('draft_date', 'ASC')
            ->get()
            ->map(function ($post) {
                $post->calendar_status = 'draft';
                $post->calendar_date = $post->draft_date;
                return $post;
            });

        // Merge all events
        $allEvents = $publishedPosts->concat($scheduledPosts)->concat($draftDatePosts);

        // Group by date
        $grouped = $allEvents->groupBy('calendar_date')->map(function ($items, $date) {
            return [
                'date' => $date,
                'count' => $items->count(),
                'events' => $items->values(),
            ];
        })->values();

        // Summary counts
        $totalPublished = $publishedPosts->count();
        $totalScheduled = $scheduledPosts->count();
        $totalDrafts = $draftDatePosts->count();

        $data = [
            'year' => (int) $year,
            'month' => (int) $month,
            'summary' => [
                'published' => $totalPublished,
                'scheduled' => $totalScheduled,
                'drafts' => $totalDrafts,
                'total' => $totalPublished + $totalScheduled + $totalDrafts,
            ],
            'days' => $grouped,
        ];

        return GlobalFunction::sendDataResponse(true, 'calendar events fetched', $data);
    }

    /**
     * Fetch best time to post analytics for the authenticated user.
     * Returns hourly/daily engagement data to suggest optimal posting times.
     */
    public function fetchBestTimeToPost(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if (!$user) {
            return GlobalFunction::sendSimpleResponse(false, 'User not found!');
        }

        // Fetch pre-calculated analytics
        $analytics = DB::table('tbl_posting_analytics')
            ->where('user_id', $user->id)
            ->orderBy('avg_engagement_rate', 'DESC')
            ->get();

        if ($analytics->isEmpty()) {
            // Calculate on-the-fly from recent posts if no pre-calculated data
            $analytics = $this->calculateAnalyticsOnTheFly($user->id);
        }

        // Find best hours (top 3)
        $bestHours = $analytics->sortByDesc('avg_engagement_rate')
            ->take(3)
            ->values()
            ->map(fn($a) => [
                'hour' => (int) $a->hour_of_day,
                'day' => (int) $a->day_of_week,
                'avg_views' => round((float) $a->avg_views, 1),
                'avg_likes' => round((float) $a->avg_likes, 1),
                'avg_engagement_rate' => round((float) $a->avg_engagement_rate, 2),
                'sample_count' => (int) $a->sample_count,
            ]);

        // Hourly aggregation (average across all days)
        $hourlyData = $analytics->groupBy('hour_of_day')->map(function ($items, $hour) {
            return [
                'hour' => (int) $hour,
                'avg_engagement_rate' => round($items->avg('avg_engagement_rate'), 2),
                'avg_views' => round($items->avg('avg_views'), 1),
                'sample_count' => $items->sum('sample_count'),
            ];
        })->sortBy('hour')->values();

        // Daily aggregation (average across all hours)
        $dailyData = $analytics->groupBy('day_of_week')->map(function ($items, $day) {
            $dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
            return [
                'day' => (int) $day,
                'day_name' => $dayNames[(int) $day] ?? '',
                'avg_engagement_rate' => round($items->avg('avg_engagement_rate'), 2),
                'avg_views' => round($items->avg('avg_views'), 1),
                'sample_count' => $items->sum('sample_count'),
            ];
        })->sortBy('day')->values();

        $data = [
            'best_times' => $bestHours,
            'hourly' => $hourlyData,
            'daily' => $dailyData,
            'total_samples' => $analytics->sum('sample_count'),
        ];

        return GlobalFunction::sendDataResponse(true, 'best time analytics fetched', $data);
    }

    /**
     * Update the draft_date for a post (for calendar planning).
     */
    public function updateDraftDate(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if (!$user) {
            return GlobalFunction::sendSimpleResponse(false, 'User not found!');
        }

        $rules = [
            'post_id' => 'required',
            'draft_date' => 'nullable|date',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return GlobalFunction::sendSimpleResponse(false, $validator->errors()->first());
        }

        $post = Posts::where('id', $request->post_id)
            ->where('user_id', $user->id)
            ->first();

        if (!$post) {
            return GlobalFunction::sendSimpleResponse(false, 'Post not found');
        }

        $post->draft_date = $request->draft_date;
        $post->save();

        return GlobalFunction::sendSimpleResponse(true, 'draft date updated');
    }

    /**
     * Bulk schedule multiple posts at once.
     * Accepts array of {post_id, scheduled_at} pairs.
     */
    public function bulkSchedule(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if (!$user) {
            return GlobalFunction::sendSimpleResponse(false, 'User not found!');
        }

        $rules = ['items' => 'required|array|min:1|max:20'];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return GlobalFunction::sendSimpleResponse(false, $validator->errors()->first());
        }

        $scheduled = 0;
        $failed = 0;

        foreach ($request->items as $item) {
            $postId = $item['post_id'] ?? null;
            $scheduledAt = $item['scheduled_at'] ?? null;

            if (!$postId || !$scheduledAt) {
                $failed++;
                continue;
            }

            $post = Posts::where('id', $postId)
                ->where('user_id', $user->id)
                ->first();

            if (!$post) {
                $failed++;
                continue;
            }

            $scheduleTime = Carbon::parse($scheduledAt);
            if ($scheduleTime->isPast()) {
                $failed++;
                continue;
            }

            $post->scheduled_at = $scheduleTime;
            $post->post_status = Constants::postStatusScheduled;
            $post->draft_date = null; // Clear draft_date when scheduled
            $post->save();
            $scheduled++;
        }

        return GlobalFunction::sendDataResponse(true, 'bulk schedule complete', [
            'scheduled' => $scheduled,
            'failed' => $failed,
        ]);
    }

    /**
     * Calculate analytics on-the-fly for users without pre-calculated data.
     */
    private function calculateAnalyticsOnTheFly(int $userId)
    {
        $posts = Posts::where('user_id', $userId)
            ->where('post_status', Constants::postStatusPublished)
            ->where('created_at', '>=', Carbon::now()->subMonths(3))
            ->select('views', 'likes', 'comments', 'shares', 'saves', 'created_at')
            ->get();

        if ($posts->isEmpty()) {
            return collect();
        }

        $grouped = $posts->groupBy(function ($post) {
            $dt = Carbon::parse($post->created_at);
            return $dt->dayOfWeek . '_' . $dt->hour;
        });

        return $grouped->map(function ($items, $key) {
            [$day, $hour] = explode('_', $key);
            $avgViews = $items->avg('views');
            $avgLikes = $items->avg('likes');
            $totalEngagement = $items->avg(function ($p) {
                return $p->likes + $p->comments + $p->shares + $p->saves;
            });
            $avgViewsSafe = max($avgViews, 1);
            return (object) [
                'hour_of_day' => (int) $hour,
                'day_of_week' => (int) $day,
                'avg_views' => $avgViews,
                'avg_likes' => $avgLikes,
                'avg_engagement_rate' => ($totalEngagement / $avgViewsSafe) * 100,
                'sample_count' => $items->count(),
            ];
        })->values();
    }
}
