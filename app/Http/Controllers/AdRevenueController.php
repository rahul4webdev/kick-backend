<?php

namespace App\Http\Controllers;

use App\Models\AdImpression;
use App\Models\AdRevenueEnrollment;
use App\Models\AdRevenuePayout;
use App\Models\CoinTransaction;
use App\Models\Constants;
use App\Models\GlobalFunction;
use App\Models\GlobalSettings;
use App\Models\Posts;
use App\Models\Users;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AdRevenueController extends Controller
{
    // ─── API: Log an ad impression ───
    public function logAdImpression(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if (!$user) {
            return GlobalFunction::sendSimpleResponse(false, 'User not found!');
        }

        $rules = [
            'post_id' => 'nullable|integer',
            'creator_id' => 'required|integer',
            'ad_type' => 'required|string|max:30',
            'ad_network' => 'nullable|string|max:30',
            'platform' => 'nullable|string|max:10',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return GlobalFunction::sendSimpleResponse(false, $validator->errors()->first());
        }

        // Only log for enrolled creators
        $enrollment = AdRevenueEnrollment::where('user_id', $request->creator_id)
            ->where('status', AdRevenueEnrollment::STATUS_APPROVED)
            ->first();

        if (!$enrollment) {
            return GlobalFunction::sendSimpleResponse(true, 'logged'); // silently skip
        }

        $settings = GlobalSettings::getCached();
        $ecpmRate = (float) ($settings->ecpm_rate ?? 2.00);
        $estimatedRevenue = $ecpmRate / 1000; // revenue per single impression

        AdImpression::create([
            'post_id' => $request->post_id,
            'creator_id' => $request->creator_id,
            'viewer_id' => $user->id,
            'ad_type' => $request->ad_type,
            'ad_network' => $request->ad_network ?? 'admob',
            'estimated_revenue' => $estimatedRevenue,
            'platform' => $request->platform ?? 'android',
        ]);

        return GlobalFunction::sendSimpleResponse(true, 'logged');
    }

    // ─── API: Enroll in ad revenue share program ───
    public function enrollInAdRevenueShare(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if (!$user) {
            return GlobalFunction::sendSimpleResponse(false, 'User not found!');
        }
        if ($user->is_freez == 1) {
            return GlobalFunction::sendSimpleResponse(false, 'Account is frozen!');
        }

        // Check if already enrolled
        $existing = AdRevenueEnrollment::where('user_id', $user->id)->first();
        if ($existing) {
            return GlobalFunction::sendDataResponse(true, 'already enrolled', [
                'enrollment' => $existing,
            ]);
        }

        // Eligibility checks
        $settings = GlobalSettings::getCached();
        $minFollowers = (int) ($settings->min_followers_for_monetization ?? 1000);

        if (($user->follower_count ?? 0) < $minFollowers) {
            return GlobalFunction::sendSimpleResponse(false, "You need at least $minFollowers followers to enroll!");
        }
        if ($user->is_monetized != 1) {
            return GlobalFunction::sendSimpleResponse(false, 'You must be monetized first!');
        }

        $totalViews = Posts::where('user_id', $user->id)->sum('views') ?? 0;

        $enrollment = AdRevenueEnrollment::create([
            'user_id' => $user->id,
            'status' => AdRevenueEnrollment::STATUS_PENDING,
            'min_followers_at_enrollment' => $user->follower_count ?? 0,
            'min_views_at_enrollment' => $totalViews,
        ]);

        return GlobalFunction::sendDataResponse(true, 'Enrollment submitted!', [
            'enrollment' => $enrollment,
        ]);
    }

    // ─── API: Fetch enrollment status ───
    public function fetchAdRevenueStatus(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if (!$user) {
            return GlobalFunction::sendSimpleResponse(false, 'User not found!');
        }

        $enrollment = AdRevenueEnrollment::where('user_id', $user->id)->first();
        $settings = GlobalSettings::getCached();

        $data = [
            'is_enrolled' => $enrollment && $enrollment->status == AdRevenueEnrollment::STATUS_APPROVED,
            'enrollment' => $enrollment,
            'ecpm_rate' => (float) ($settings->ecpm_rate ?? 2.00),
            'revenue_share_percent' => (int) ($settings->creator_revenue_share ?? 55),
            'min_followers_required' => (int) ($settings->min_followers_for_monetization ?? 1000),
            'is_monetized' => $user->is_monetized == 1,
        ];

        return GlobalFunction::sendDataResponse(true, 'status fetched', $data);
    }

    // ─── API: Fetch ad revenue summary for creator ───
    public function fetchAdRevenueSummary(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if (!$user) {
            return GlobalFunction::sendSimpleResponse(false, 'User not found!');
        }

        $enrollment = AdRevenueEnrollment::where('user_id', $user->id)
            ->where('status', AdRevenueEnrollment::STATUS_APPROVED)
            ->first();

        if (!$enrollment) {
            return GlobalFunction::sendSimpleResponse(false, 'Not enrolled in ad revenue share!');
        }

        $settings = GlobalSettings::getCached();
        $revenueShare = (int) ($settings->creator_revenue_share ?? 55);

        // Total impressions & revenue
        $totalImpressions = AdImpression::where('creator_id', $user->id)->count();
        $totalRevenue = (float) AdImpression::where('creator_id', $user->id)->sum('estimated_revenue');
        $creatorTotal = round($totalRevenue * ($revenueShare / 100), 4);

        // Today
        $todayImpressions = AdImpression::where('creator_id', $user->id)
            ->whereDate('created_at', Carbon::today())
            ->count();
        $todayRevenue = (float) AdImpression::where('creator_id', $user->id)
            ->whereDate('created_at', Carbon::today())
            ->sum('estimated_revenue');
        $creatorToday = round($todayRevenue * ($revenueShare / 100), 4);

        // This month
        $monthImpressions = AdImpression::where('creator_id', $user->id)
            ->whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->count();
        $monthRevenue = (float) AdImpression::where('creator_id', $user->id)
            ->whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->sum('estimated_revenue');
        $creatorMonth = round($monthRevenue * ($revenueShare / 100), 4);

        // By ad type breakdown
        $byAdType = AdImpression::where('creator_id', $user->id)
            ->select('ad_type', DB::raw('COUNT(*) as impressions'), DB::raw('SUM(estimated_revenue) as revenue'))
            ->groupBy('ad_type')
            ->get()
            ->map(function ($item) use ($revenueShare) {
                return [
                    'ad_type' => $item->ad_type,
                    'impressions' => (int) $item->impressions,
                    'total_revenue' => round((float) $item->revenue, 4),
                    'creator_share' => round((float) $item->revenue * ($revenueShare / 100), 4),
                ];
            });

        // Daily breakdown (last 30 days)
        $dailyBreakdown = AdImpression::where('creator_id', $user->id)
            ->where('created_at', '>=', Carbon::now()->subDays(30))
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as impressions'),
                DB::raw('SUM(estimated_revenue) as revenue')
            )
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date', 'DESC')
            ->limit(30)
            ->get()
            ->map(function ($item) use ($revenueShare) {
                return [
                    'date' => $item->date,
                    'impressions' => (int) $item->impressions,
                    'total_revenue' => round((float) $item->revenue, 4),
                    'creator_share' => round((float) $item->revenue * ($revenueShare / 100), 4),
                ];
            });

        // Top earning posts
        $topPosts = AdImpression::where('creator_id', $user->id)
            ->whereNotNull('post_id')
            ->select('post_id', DB::raw('COUNT(*) as impressions'), DB::raw('SUM(estimated_revenue) as revenue'))
            ->groupBy('post_id')
            ->orderByDesc('revenue')
            ->limit(10)
            ->get()
            ->map(function ($item) use ($revenueShare) {
                $post = Posts::select('id', 'thumbnail', 'description', 'views', 'likes')
                    ->find($item->post_id);
                return [
                    'post_id' => $item->post_id,
                    'thumbnail' => $post->thumbnail ?? null,
                    'description' => $post->description ?? '',
                    'views' => (int) ($post->views ?? 0),
                    'likes' => (int) ($post->likes ?? 0),
                    'impressions' => (int) $item->impressions,
                    'total_revenue' => round((float) $item->revenue, 4),
                    'creator_share' => round((float) $item->revenue * ($revenueShare / 100), 4),
                ];
            });

        // Payout history
        $payouts = AdRevenuePayout::where('user_id', $user->id)
            ->orderByDesc('period_end')
            ->limit(12)
            ->get();

        // Total coins earned from ad revenue
        $totalCoinsEarned = CoinTransaction::where('user_id', $user->id)
            ->where('type', Constants::txnAdRevenue)
            ->where('direction', Constants::credit)
            ->sum('coins');

        $data = [
            'revenue_share_percent' => $revenueShare,
            'ecpm_rate' => (float) ($settings->ecpm_rate ?? 2.00),
            'total' => [
                'impressions' => $totalImpressions,
                'revenue' => $totalRevenue,
                'creator_share' => $creatorTotal,
            ],
            'today' => [
                'impressions' => $todayImpressions,
                'revenue' => $todayRevenue,
                'creator_share' => $creatorToday,
            ],
            'this_month' => [
                'impressions' => $monthImpressions,
                'revenue' => $monthRevenue,
                'creator_share' => $creatorMonth,
            ],
            'by_ad_type' => $byAdType,
            'daily_breakdown' => $dailyBreakdown,
            'top_earning_posts' => $topPosts,
            'payouts' => $payouts,
            'total_coins_earned' => (int) $totalCoinsEarned,
        ];

        return GlobalFunction::sendDataResponse(true, 'ad revenue summary fetched', $data);
    }

    // ─── CRON/Admin: Process monthly ad revenue payouts ───
    public function processMonthlyAdRevenue(Request $request)
    {
        $settings = GlobalSettings::getCached();
        $revenueShare = (int) ($settings->creator_revenue_share ?? 55);
        $coinValue = (float) ($settings->coin_value ?: 0.01);
        $coinsPerDollar = (int) round(1 / $coinValue);

        // Process previous month
        $periodStart = Carbon::now()->subMonth()->startOfMonth()->toDateString();
        $periodEnd = Carbon::now()->subMonth()->endOfMonth()->toDateString();

        // Get all enrolled creators with impressions this period
        $creators = AdImpression::whereBetween('created_at', [$periodStart, $periodEnd . ' 23:59:59'])
            ->select('creator_id', DB::raw('COUNT(*) as impressions'), DB::raw('SUM(estimated_revenue) as revenue'))
            ->groupBy('creator_id')
            ->having('revenue', '>', 0)
            ->get();

        $processed = 0;

        foreach ($creators as $creatorData) {
            // Check enrollment
            $enrollment = AdRevenueEnrollment::where('user_id', $creatorData->creator_id)
                ->where('status', AdRevenueEnrollment::STATUS_APPROVED)
                ->first();

            if (!$enrollment) continue;

            // Check if already processed
            $existing = AdRevenuePayout::where('user_id', $creatorData->creator_id)
                ->where('period_start', $periodStart)
                ->where('period_end', $periodEnd)
                ->first();

            if ($existing) continue;

            $totalRevenue = (float) $creatorData->revenue;
            $creatorShare = round($totalRevenue * ($revenueShare / 100), 4);
            $platformShare = round($totalRevenue - $creatorShare, 4);
            $coinsToCredit = (int) round($creatorShare * $coinsPerDollar);

            if ($coinsToCredit < 1) continue;

            DB::transaction(function () use ($creatorData, $periodStart, $periodEnd, $totalRevenue, $creatorShare, $platformShare, $coinsToCredit) {
                // Credit creator's wallet
                $creator = Users::find($creatorData->creator_id);
                if (!$creator) return;

                $creator->coin_wallet += $coinsToCredit;
                $creator->coin_collected_lifetime += $coinsToCredit;
                $creator->save();

                // Log transaction
                $txn = new CoinTransaction();
                $txn->user_id = $creatorData->creator_id;
                $txn->type = Constants::txnAdRevenue;
                $txn->coins = $coinsToCredit;
                $txn->direction = Constants::credit;
                $txn->note = "Ad revenue for $periodStart to $periodEnd";
                $txn->save();

                // Create payout record
                AdRevenuePayout::create([
                    'user_id' => $creatorData->creator_id,
                    'period_start' => $periodStart,
                    'period_end' => $periodEnd,
                    'total_impressions' => (int) $creatorData->impressions,
                    'total_estimated_revenue' => $totalRevenue,
                    'creator_share' => $creatorShare,
                    'platform_share' => $platformShare,
                    'coins_credited' => $coinsToCredit,
                    'transaction_id' => $txn->id,
                    'status' => AdRevenuePayout::STATUS_PROCESSED,
                    'processed_at' => Carbon::now(),
                ]);
            });

            $processed++;
        }

        return GlobalFunction::sendDataResponse(true, 'monthly ad revenue processed', [
            'period' => "$periodStart to $periodEnd",
            'creators_processed' => $processed,
        ]);
    }

    // ─── Admin: View ad revenue dashboard ───
    public function adRevenueAdmin()
    {
        return view('ad_revenue');
    }

    // ─── Admin: List enrollments ───
    public function listAdRevenueEnrollments(Request $request)
    {
        $statusFilter = $request->status ?? 'all';

        $totalData = AdRevenueEnrollment::count();
        $query = AdRevenueEnrollment::with('user:id,username,fullname,profile_photo,follower_count,is_verify');

        if ($statusFilter === 'pending') {
            $query->where('status', AdRevenueEnrollment::STATUS_PENDING);
        } elseif ($statusFilter === 'approved') {
            $query->where('status', AdRevenueEnrollment::STATUS_APPROVED);
        } elseif ($statusFilter === 'rejected') {
            $query->where('status', AdRevenueEnrollment::STATUS_REJECTED);
        }

        if ($request->has('search') && $request->search['value']) {
            $search = $request->search['value'];
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('username', 'LIKE', "%$search%")
                    ->orWhere('fullname', 'LIKE', "%$search%");
            });
        }

        $filteredData = $query->count();

        $enrollments = $query
            ->orderByDesc('created_at')
            ->skip($request->start ?? 0)
            ->take($request->length ?? 10)
            ->get();

        $data = [];
        foreach ($enrollments as $e) {
            $statusLabel = match ($e->status) {
                AdRevenueEnrollment::STATUS_PENDING => '<span class="badge bg-warning">Pending</span>',
                AdRevenueEnrollment::STATUS_APPROVED => '<span class="badge bg-success">Approved</span>',
                AdRevenueEnrollment::STATUS_REJECTED => '<span class="badge bg-danger">Rejected</span>',
                default => '<span class="badge bg-secondary">Unknown</span>',
            };

            $actions = '';
            if ($e->status == AdRevenueEnrollment::STATUS_PENDING) {
                $actions .= '<button class="btn btn-success btn-sm me-1" onclick="updateEnrollment(' . $e->id . ', 1)">Approve</button>';
                $actions .= '<button class="btn btn-danger btn-sm" onclick="updateEnrollment(' . $e->id . ', 2)">Reject</button>';
            }

            $totalImpressions = AdImpression::where('creator_id', $e->user_id)->count();
            $totalRevenue = round((float) AdImpression::where('creator_id', $e->user_id)->sum('estimated_revenue'), 4);

            $data[] = [
                $e->user->username ?? 'N/A',
                $e->user->fullname ?? 'N/A',
                number_format($e->user->follower_count ?? 0),
                number_format($e->min_views_at_enrollment),
                number_format($totalImpressions),
                '$' . number_format($totalRevenue, 4),
                $statusLabel,
                $e->created_at?->format('Y-m-d'),
                $actions,
            ];
        }

        return response()->json([
            'draw' => $request->draw ?? 1,
            'recordsTotal' => $totalData,
            'recordsFiltered' => $filteredData,
            'data' => $data,
        ]);
    }

    // ─── Admin: Update enrollment status ───
    public function updateAdRevenueEnrollment(Request $request)
    {
        $rules = [
            'enrollment_id' => 'required|exists:tbl_ad_revenue_enrollment,id',
            'status' => 'required|in:1,2',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return GlobalFunction::sendSimpleResponse(false, $validator->errors()->first());
        }

        $enrollment = AdRevenueEnrollment::find($request->enrollment_id);
        $enrollment->status = $request->status;

        if ($request->status == AdRevenueEnrollment::STATUS_APPROVED) {
            $enrollment->approved_at = Carbon::now();
        }
        if ($request->has('rejection_reason')) {
            $enrollment->rejection_reason = $request->rejection_reason;
        }

        $enrollment->save();

        return GlobalFunction::sendSimpleResponse(true, 'Enrollment updated!');
    }

    // ─── Admin: List payouts ───
    public function listAdRevenuePayouts(Request $request)
    {
        $totalData = AdRevenuePayout::count();
        $query = AdRevenuePayout::with('user:id,username,fullname,profile_photo');

        if ($request->has('search') && $request->search['value']) {
            $search = $request->search['value'];
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('username', 'LIKE', "%$search%");
            });
        }

        $filteredData = $query->count();

        $payouts = $query
            ->orderByDesc('period_end')
            ->skip($request->start ?? 0)
            ->take($request->length ?? 10)
            ->get();

        $data = [];
        foreach ($payouts as $p) {
            $statusLabel = match ($p->status) {
                AdRevenuePayout::STATUS_PENDING => '<span class="badge bg-warning">Pending</span>',
                AdRevenuePayout::STATUS_PROCESSED => '<span class="badge bg-success">Processed</span>',
                AdRevenuePayout::STATUS_PAID => '<span class="badge bg-primary">Paid</span>',
                default => '<span class="badge bg-secondary">Unknown</span>',
            };

            $data[] = [
                $p->user->username ?? 'N/A',
                $p->period_start->format('M d') . ' - ' . $p->period_end->format('M d, Y'),
                number_format($p->total_impressions),
                '$' . number_format($p->total_estimated_revenue, 4),
                '$' . number_format($p->creator_share, 4),
                number_format($p->coins_credited),
                $statusLabel,
                $p->processed_at ? $p->processed_at->format('Y-m-d H:i') : '-',
            ];
        }

        return response()->json([
            'draw' => $request->draw ?? 1,
            'recordsTotal' => $totalData,
            'recordsFiltered' => $filteredData,
            'data' => $data,
        ]);
    }

    // ─── Admin: Revenue overview stats ───
    public function fetchAdRevenueStats()
    {
        $totalImpressions = AdImpression::count();
        $totalRevenue = (float) AdImpression::sum('estimated_revenue');
        $totalEnrolled = AdRevenueEnrollment::where('status', AdRevenueEnrollment::STATUS_APPROVED)->count();
        $totalPending = AdRevenueEnrollment::where('status', AdRevenueEnrollment::STATUS_PENDING)->count();
        $totalPaidOut = (float) AdRevenuePayout::where('status', '>=', AdRevenuePayout::STATUS_PROCESSED)->sum('creator_share');
        $platformRevenue = (float) AdRevenuePayout::where('status', '>=', AdRevenuePayout::STATUS_PROCESSED)->sum('platform_share');

        return response()->json([
            'total_impressions' => $totalImpressions,
            'total_revenue' => round($totalRevenue, 2),
            'total_enrolled' => $totalEnrolled,
            'total_pending' => $totalPending,
            'total_paid_out' => round($totalPaidOut, 2),
            'platform_revenue' => round($platformRevenue, 2),
        ]);
    }
}
