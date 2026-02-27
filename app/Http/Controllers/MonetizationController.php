<?php

namespace App\Http\Controllers;

use App\Models\CoinTransaction;
use App\Models\Constants;
use App\Models\GlobalFunction;
use App\Models\GlobalSettings;
use App\Models\Posts;
use App\Models\RewardedAdClaim;
use App\Models\Users;
use App\Models\VerificationDocument;
use App\Jobs\ProcessUserNotificationJob;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class MonetizationController extends Controller
{
    public function fetchMonetizationStatus(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if (!$user) {
            return GlobalFunction::sendSimpleResponse(false, 'User not found!');
        }

        $settings = GlobalSettings::getCached();
        $documents = VerificationDocument::where('user_id', $user->id)->get();

        $data = [
            'is_monetized' => $user->is_monetized,
            'monetization_status' => $user->monetization_status,
            'follower_count' => $user->follower_count ?? 0,
            'min_followers_required' => $settings->min_followers_for_monetization,
            'business_status' => $user->business_status,
            'has_kyc_documents' => $documents->count() > 0,
            'verification_documents' => $documents,
            'requirements' => [
                'has_min_followers' => ($user->follower_count ?? 0) >= $settings->min_followers_for_monetization,
                'has_approved_business' => $user->business_status == Constants::businessStatusApproved,
                'has_kyc_uploaded' => $documents->count() > 0,
            ],
        ];

        return GlobalFunction::sendDataResponse(true, 'monetization status fetched', $data);
    }

    public function applyForMonetization(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if (!$user) {
            return GlobalFunction::sendSimpleResponse(false, 'User not found!');
        }
        if ($user->is_freez == 1) {
            return GlobalFunction::sendSimpleResponse(false, 'this user is freezed!');
        }

        if ($user->is_monetized == 1) {
            return GlobalFunction::sendSimpleResponse(false, 'you are already monetized!');
        }
        if ($user->monetization_status == Constants::businessStatusPending) {
            return GlobalFunction::sendSimpleResponse(false, 'your monetization application is already pending!');
        }

        $settings = GlobalSettings::getCached();

        if ($user->business_status != Constants::businessStatusApproved) {
            return GlobalFunction::sendSimpleResponse(false, 'you need an approved business account first!');
        }
        if (($user->follower_count ?? 0) < $settings->min_followers_for_monetization) {
            return GlobalFunction::sendSimpleResponse(false, 'you need at least ' . $settings->min_followers_for_monetization . ' followers!');
        }

        $kycCount = VerificationDocument::where('user_id', $user->id)->count();
        if ($kycCount == 0) {
            return GlobalFunction::sendSimpleResponse(false, 'please upload KYC documents first!');
        }

        $user->monetization_status = Constants::businessStatusPending;
        $user->save();

        $user = GlobalFunction::prepareUserFullData($user->id);
        return GlobalFunction::sendDataResponse(true, 'monetization application submitted!', $user);
    }

    public function submitKycDocument(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if (!$user) {
            return GlobalFunction::sendSimpleResponse(false, 'User not found!');
        }
        if ($user->is_freez == 1) {
            return GlobalFunction::sendSimpleResponse(false, 'this user is freezed!');
        }

        $rules = [
            'document_type' => 'required|string',
            'document' => 'required|file',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $documentUrl = GlobalFunction::saveFileAndGivePath($request->document);

        $doc = new VerificationDocument();
        $doc->user_id = $user->id;
        $doc->document_type = $request->document_type;
        $doc->document_url = $documentUrl;
        $doc->status = 0; // pending
        $doc->save();

        return GlobalFunction::sendDataResponse(true, 'document uploaded successfully', $doc);
    }

    public function fetchEarningsSummary(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if (!$user) {
            return GlobalFunction::sendSimpleResponse(false, 'User not found!');
        }

        $totalEarnings = CoinTransaction::where('user_id', $user->id)
            ->where('direction', Constants::credit)
            ->sum('coins');

        $todayEarnings = CoinTransaction::where('user_id', $user->id)
            ->where('direction', Constants::credit)
            ->whereDate('created_at', Carbon::today())
            ->sum('coins');

        $thisMonthEarnings = CoinTransaction::where('user_id', $user->id)
            ->where('direction', Constants::credit)
            ->whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->sum('coins');

        $topSupporters = CoinTransaction::where('user_id', $user->id)
            ->where('type', Constants::txnGiftReceived)
            ->whereNotNull('related_user_id')
            ->select('related_user_id', DB::raw('SUM(coins) as total_coins'))
            ->groupBy('related_user_id')
            ->orderByDesc('total_coins')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                $supporter = Users::select('id', 'username', 'fullname', 'profile_photo', 'is_verify')
                    ->find($item->related_user_id);
                return [
                    'user_id' => $item->related_user_id,
                    'username' => $supporter->username ?? '',
                    'fullname' => $supporter->fullname ?? '',
                    'profile_photo' => $supporter->profile_photo,
                    'is_verify' => $supporter->is_verify ?? 0,
                    'total_coins' => $item->total_coins,
                ];
            });

        $earningsBySource = CoinTransaction::where('user_id', $user->id)
            ->where('direction', Constants::credit)
            ->select('type', DB::raw('SUM(coins) as total_coins'))
            ->groupBy('type')
            ->pluck('total_coins', 'type');

        // Ad revenue estimation based on views and eCPM
        $totalViews = Posts::where('user_id', $user->id)->sum('views') ?? 0;
        $totalLikes = Posts::where('user_id', $user->id)->sum('likes') ?? 0;
        $totalShares = Posts::where('user_id', $user->id)->sum('shares') ?? 0;
        $totalPosts = Posts::where('user_id', $user->id)->count();

        $todayViews = Posts::where('user_id', $user->id)
            ->whereDate('created_at', Carbon::today())
            ->sum('views') ?? 0;

        $thisMonthViews = Posts::where('user_id', $user->id)
            ->whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->sum('views') ?? 0;

        $ecpmRate = (float) ($settings->ecpm_rate ?? 2.00);
        $revenueShare = (int) ($settings->creator_revenue_share ?? 55);

        $estimatedTotalAdRevenue = round(($totalViews / 1000) * $ecpmRate * ($revenueShare / 100), 2);
        $estimatedTodayAdRevenue = round(($todayViews / 1000) * $ecpmRate * ($revenueShare / 100), 2);
        $estimatedMonthAdRevenue = round(($thisMonthViews / 1000) * $ecpmRate * ($revenueShare / 100), 2);

        // Top performing posts
        $topPosts = Posts::where('user_id', $user->id)
            ->select('id', 'thumbnail', 'description', 'views', 'likes', 'shares', 'created_at')
            ->orderByDesc('views')
            ->limit(5)
            ->get()
            ->map(function ($post) use ($ecpmRate, $revenueShare) {
                return [
                    'id' => $post->id,
                    'thumbnail' => $post->thumbnail,
                    'description' => $post->description,
                    'views' => (int) $post->views,
                    'likes' => (int) $post->likes,
                    'shares' => (int) $post->shares,
                    'estimated_revenue' => round(($post->views / 1000) * $ecpmRate * ($revenueShare / 100), 2),
                ];
            });

        $data = [
            'total_earnings' => (int) $totalEarnings,
            'today_earnings' => (int) $todayEarnings,
            'this_month_earnings' => (int) $thisMonthEarnings,
            'top_supporters' => $topSupporters,
            'earnings_by_source' => $earningsBySource,
            'ad_revenue' => [
                'ecpm_rate' => $ecpmRate,
                'revenue_share_percent' => $revenueShare,
                'total_impressions' => (int) $totalViews,
                'today_impressions' => (int) $todayViews,
                'this_month_impressions' => (int) $thisMonthViews,
                'estimated_total_revenue' => $estimatedTotalAdRevenue,
                'estimated_today_revenue' => $estimatedTodayAdRevenue,
                'estimated_month_revenue' => $estimatedMonthAdRevenue,
            ],
            'content_stats' => [
                'total_posts' => $totalPosts,
                'total_views' => (int) $totalViews,
                'total_likes' => (int) $totalLikes,
                'total_shares' => (int) $totalShares,
            ],
            'top_performing_posts' => $topPosts,
        ];

        return GlobalFunction::sendDataResponse(true, 'earnings summary fetched', $data);
    }

    public function fetchTransactionHistory(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if (!$user) {
            return GlobalFunction::sendSimpleResponse(false, 'User not found!');
        }

        $rules = [
            'limit' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $query = CoinTransaction::where('user_id', $user->id)
            ->with(['relatedUser:' . Constants::userPublicFields])
            ->orderBy('id', 'DESC')
            ->limit($request->limit);

        if ($request->has('last_item_id')) {
            $query->where('id', '<', $request->last_item_id);
        }
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        $transactions = $query->get();

        return GlobalFunction::sendDataResponse(true, 'transaction history fetched', $transactions);
    }

    public function claimRewardedAd(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if (!$user) {
            return GlobalFunction::sendSimpleResponse(false, 'User not found!');
        }
        if ($user->is_freez == 1) {
            return GlobalFunction::sendSimpleResponse(false, 'this user is freezed!');
        }

        $settings = GlobalSettings::getCached();
        $today = Carbon::today()->toDateString();

        $claim = RewardedAdClaim::where('user_id', $user->id)
            ->where('claim_date', $today)
            ->first();

        $currentCount = $claim ? $claim->claim_count : 0;

        if ($currentCount >= $settings->max_rewarded_ads_daily) {
            return GlobalFunction::sendSimpleResponse(false, 'daily ad reward limit reached!');
        }

        // Grant coins
        $rewardCoins = $settings->reward_coins_per_ad;
        $user->coin_wallet += $rewardCoins;
        $user->coin_collected_lifetime += $rewardCoins;
        $user->save();

        // Update or create claim record
        if ($claim) {
            $claim->claim_count += 1;
            $claim->save();
        } else {
            RewardedAdClaim::create([
                'user_id' => $user->id,
                'claim_date' => $today,
                'claim_count' => 1,
            ]);
        }

        // Log transaction
        $txn = new CoinTransaction();
        $txn->user_id = $user->id;
        $txn->type = Constants::txnAdReward;
        $txn->coins = $rewardCoins;
        $txn->direction = Constants::credit;
        $txn->save();

        $remaining = $settings->max_rewarded_ads_daily - ($currentCount + 1);

        $data = [
            'remaining_ads_today' => $remaining,
            'user' => GlobalFunction::prepareUserFullData($user->id),
        ];

        return GlobalFunction::sendDataResponse(true, 'ad reward claimed!', $data);
    }

    // Admin: Update monetization status
    public function updateMonetizationStatus(Request $request)
    {
        $rules = [
            'user_id' => 'required|exists:tbl_users,id',
            'monetization_status' => 'required|in:2,3',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $user = Users::find($request->user_id);

        if ($request->monetization_status == Constants::businessStatusApproved) {
            $user->is_monetized = true;
            $user->monetization_status = Constants::businessStatusApproved;
        } else {
            $user->is_monetized = false;
            $user->monetization_status = Constants::businessStatusRejected;
        }
        $user->save();

        ProcessUserNotificationJob::dispatch(Constants::notify_monetization_status, 0, $user->id, $user->id);

        return GlobalFunction::sendSimpleResponse(true, 'monetization status updated!');
    }

    // Admin: Review KYC document
    public function reviewKycDocument(Request $request)
    {
        $rules = [
            'document_id' => 'required|exists:verification_documents,id',
            'status' => 'required|in:1,2',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $doc = VerificationDocument::find($request->document_id);
        $doc->status = $request->status;
        if ($request->has('rejection_reason')) {
            $doc->rejection_reason = $request->rejection_reason;
        }
        if ($request->status == 1) {
            $doc->verified_at = Carbon::now();
        }
        $doc->save();

        return GlobalFunction::sendSimpleResponse(true, 'document review saved!');
    }
}
