<?php

namespace App\Http\Controllers;

use App\Models\CoinTransaction;
use App\Models\Constants;
use App\Models\CreatorSubscription;
use App\Models\GlobalFunction;
use App\Models\SubscriptionTier;
use App\Models\Users;
use App\Jobs\ProcessUserNotificationJob;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CreatorSubscriptionController extends Controller
{
    /**
     * Creator: Enable subscriptions on their profile
     */
    public function enableSubscriptions(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));

        $user->subscriptions_enabled = true;
        $user->save();

        return GlobalFunction::sendSimpleResponse(true, 'Subscriptions enabled');
    }

    /**
     * Creator: Disable subscriptions
     */
    public function disableSubscriptions(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));

        $user->subscriptions_enabled = false;
        $user->save();

        return GlobalFunction::sendSimpleResponse(true, 'Subscriptions disabled');
    }

    /**
     * Creator: Create a subscription tier
     */
    public function createTier(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));

        $rules = [
            'name' => 'required|string|max:100',
            'price_coins' => 'required|integer|min:1',
            'description' => 'nullable|string|max:500',
            'benefits' => 'nullable|array',
            'benefits.*' => 'string|max:200',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return GlobalFunction::sendSimpleResponse(false, $validator->errors()->first());
        }

        // Max 3 tiers per creator
        $existingCount = SubscriptionTier::where('creator_id', $user->id)
            ->where('is_active', true)
            ->count();
        if ($existingCount >= 3) {
            return GlobalFunction::sendSimpleResponse(false, 'Maximum 3 subscription tiers allowed');
        }

        $tier = SubscriptionTier::create([
            'creator_id' => $user->id,
            'name' => $request->name,
            'price_coins' => $request->price_coins,
            'description' => $request->description,
            'benefits' => $request->benefits ?? [],
            'sort_order' => $existingCount,
        ]);

        // Auto-enable subscriptions
        if (!$user->subscriptions_enabled) {
            $user->subscriptions_enabled = true;
            $user->save();
        }

        return ['status' => true, 'message' => 'Tier created', 'data' => $tier];
    }

    /**
     * Creator: Update a subscription tier
     */
    public function updateTier(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));

        $rules = [
            'tier_id' => 'required|exists:tbl_subscription_tiers,id',
            'name' => 'sometimes|string|max:100',
            'price_coins' => 'sometimes|integer|min:1',
            'description' => 'nullable|string|max:500',
            'benefits' => 'nullable|array',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return GlobalFunction::sendSimpleResponse(false, $validator->errors()->first());
        }

        $tier = SubscriptionTier::where('id', $request->tier_id)
            ->where('creator_id', $user->id)
            ->first();
        if (!$tier) {
            return GlobalFunction::sendSimpleResponse(false, 'Tier not found');
        }

        if ($request->has('name')) $tier->name = $request->name;
        if ($request->has('price_coins')) $tier->price_coins = $request->price_coins;
        if ($request->has('description')) $tier->description = $request->description;
        if ($request->has('benefits')) $tier->benefits = $request->benefits;
        $tier->save();

        return ['status' => true, 'message' => 'Tier updated', 'data' => $tier];
    }

    /**
     * Creator: Delete (deactivate) a subscription tier
     */
    public function deleteTier(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));

        $tier = SubscriptionTier::where('id', $request->tier_id)
            ->where('creator_id', $user->id)
            ->first();
        if (!$tier) {
            return GlobalFunction::sendSimpleResponse(false, 'Tier not found');
        }

        $tier->is_active = false;
        $tier->save();

        return GlobalFunction::sendSimpleResponse(true, 'Tier deleted');
    }

    /**
     * Fetch subscription tiers for a creator
     */
    public function fetchTiers(Request $request)
    {
        $creatorId = $request->creator_id;
        if (!$creatorId) {
            return GlobalFunction::sendSimpleResponse(false, 'creator_id required');
        }

        $tiers = SubscriptionTier::where('creator_id', $creatorId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        // Check if current user is subscribed
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));
        $activeSub = CreatorSubscription::where('subscriber_id', $user->id)
            ->where('creator_id', $creatorId)
            ->active()
            ->first();

        return [
            'status' => true,
            'message' => '',
            'data' => [
                'tiers' => $tiers,
                'current_subscription' => $activeSub ? [
                    'id' => $activeSub->id,
                    'tier_id' => $activeSub->tier_id,
                    'expires_at' => $activeSub->expires_at->toIso8601String(),
                    'auto_renew' => $activeSub->auto_renew,
                ] : null,
            ],
        ];
    }

    /**
     * Subscriber: Subscribe to a creator's tier
     */
    public function subscribe(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));

        $rules = [
            'tier_id' => 'required|exists:tbl_subscription_tiers,id',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return GlobalFunction::sendSimpleResponse(false, $validator->errors()->first());
        }

        $tier = SubscriptionTier::where('id', $request->tier_id)
            ->where('is_active', true)
            ->first();
        if (!$tier) {
            return GlobalFunction::sendSimpleResponse(false, 'Tier not found or inactive');
        }

        // Can't subscribe to yourself
        if ($tier->creator_id == $user->id) {
            return GlobalFunction::sendSimpleResponse(false, 'Cannot subscribe to yourself');
        }

        // Check if already subscribed to this creator
        $existing = CreatorSubscription::where('subscriber_id', $user->id)
            ->where('creator_id', $tier->creator_id)
            ->active()
            ->first();
        if ($existing) {
            return GlobalFunction::sendSimpleResponse(false, 'Already subscribed to this creator');
        }

        // Check coin balance
        if ($user->coin_wallet < $tier->price_coins) {
            return GlobalFunction::sendSimpleResponse(false, 'Insufficient coins');
        }

        return DB::transaction(function () use ($user, $tier) {
            $now = Carbon::now();
            $expiresAt = $now->copy()->addMonth();

            // Deduct coins from subscriber
            $user->coin_wallet -= $tier->price_coins;
            $user->save();

            // Credit coins to creator
            $creator = Users::find($tier->creator_id);
            $creator->coin_wallet += $tier->price_coins;
            $creator->coin_collected_lifetime += $tier->price_coins;
            $creator->subscriber_count += 1;
            $creator->save();

            // Create subscription
            $subscription = CreatorSubscription::create([
                'subscriber_id' => $user->id,
                'creator_id' => $tier->creator_id,
                'tier_id' => $tier->id,
                'price_coins' => $tier->price_coins,
                'status' => CreatorSubscription::STATUS_ACTIVE,
                'auto_renew' => true,
                'started_at' => $now,
                'expires_at' => $expiresAt,
            ]);

            // Create coin transactions
            CoinTransaction::create([
                'user_id' => $user->id,
                'type' => Constants::txnSubscriptionSent,
                'coins' => $tier->price_coins,
                'direction' => Constants::debit,
                'related_user_id' => $tier->creator_id,
                'reference_id' => $subscription->id,
                'note' => "Subscribed to {$creator->fullname} ({$tier->name})",
            ]);
            CoinTransaction::create([
                'user_id' => $tier->creator_id,
                'type' => Constants::txnSubscriptionReceived,
                'coins' => $tier->price_coins,
                'direction' => Constants::credit,
                'related_user_id' => $user->id,
                'reference_id' => $subscription->id,
                'note' => "{$user->fullname} subscribed ({$tier->name})",
            ]);

            // Send notification to creator
            ProcessUserNotificationJob::dispatch(
                $user->id,
                $tier->creator_id,
                Constants::notify_new_subscriber,
                null,
                null,
                null,
            );

            return [
                'status' => true,
                'message' => 'Subscribed successfully',
                'data' => [
                    'subscription_id' => $subscription->id,
                    'expires_at' => $expiresAt->toIso8601String(),
                    'coins_remaining' => $user->coin_wallet,
                ],
            ];
        });
    }

    /**
     * Subscriber: Cancel subscription (stops auto-renewal)
     */
    public function cancelSubscription(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));

        $subscription = CreatorSubscription::where('subscriber_id', $user->id)
            ->where('creator_id', $request->creator_id)
            ->active()
            ->first();

        if (!$subscription) {
            return GlobalFunction::sendSimpleResponse(false, 'No active subscription found');
        }

        $subscription->auto_renew = false;
        $subscription->cancelled_at = Carbon::now();
        $subscription->status = CreatorSubscription::STATUS_CANCELLED;
        $subscription->save();

        // Decrement subscriber count
        Users::where('id', $subscription->creator_id)
            ->where('subscriber_count', '>', 0)
            ->decrement('subscriber_count');

        return GlobalFunction::sendSimpleResponse(true, 'Subscription cancelled. Access continues until ' . $subscription->expires_at->format('M d, Y'));
    }

    /**
     * Fetch user's active subscriptions (what they're subscribed to)
     */
    public function fetchMySubscriptions(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));

        $subscriptions = CreatorSubscription::where('subscriber_id', $user->id)
            ->where('status', CreatorSubscription::STATUS_ACTIVE)
            ->where('expires_at', '>', now())
            ->with(['creator:' . Constants::userPublicFields, 'tier:id,name,price_coins,benefits'])
            ->orderBy('expires_at', 'asc')
            ->get();

        return ['status' => true, 'message' => '', 'data' => $subscriptions];
    }

    /**
     * Creator: Fetch my subscribers
     */
    public function fetchMySubscribers(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));
        $limit = $request->limit ?? 20;

        $query = CreatorSubscription::where('creator_id', $user->id)
            ->where('status', CreatorSubscription::STATUS_ACTIVE)
            ->where('expires_at', '>', now())
            ->with(['subscriber:' . Constants::userPublicFields, 'tier:id,name,price_coins'])
            ->orderBy('created_at', 'desc');

        if ($request->lastItemId) {
            $query->where('id', '<', $request->lastItemId);
        }

        $subscribers = $query->limit($limit)->get();

        return ['status' => true, 'message' => '', 'data' => $subscribers];
    }

    /**
     * Check if current user is subscribed to a given creator
     */
    public function checkSubscription(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));

        $subscription = CreatorSubscription::where('subscriber_id', $user->id)
            ->where('creator_id', $request->creator_id)
            ->active()
            ->first();

        return [
            'status' => true,
            'message' => '',
            'data' => [
                'is_subscribed' => $subscription !== null,
                'subscription' => $subscription ? [
                    'id' => $subscription->id,
                    'tier_id' => $subscription->tier_id,
                    'tier_name' => $subscription->tier?->name,
                    'expires_at' => $subscription->expires_at->toIso8601String(),
                    'auto_renew' => $subscription->auto_renew,
                ] : null,
            ],
        ];
    }

    /**
     * Cron: Renew expired subscriptions with auto_renew=true
     */
    public static function renewExpiredSubscriptions()
    {
        $expiredSubs = CreatorSubscription::where('status', CreatorSubscription::STATUS_ACTIVE)
            ->where('auto_renew', true)
            ->where('expires_at', '<=', now())
            ->with('tier')
            ->get();

        $renewed = 0;
        $expired = 0;

        foreach ($expiredSubs as $sub) {
            $subscriber = Users::find($sub->subscriber_id);
            $creator = Users::find($sub->creator_id);
            $tier = $sub->tier;

            if (!$subscriber || !$creator || !$tier || !$tier->is_active) {
                // Expire the subscription
                $sub->status = CreatorSubscription::STATUS_EXPIRED;
                $sub->save();
                if ($creator) {
                    Users::where('id', $creator->id)->where('subscriber_count', '>', 0)->decrement('subscriber_count');
                }
                $expired++;
                continue;
            }

            // Check if subscriber has enough coins
            if ($subscriber->coin_wallet < $tier->price_coins) {
                $sub->status = CreatorSubscription::STATUS_EXPIRED;
                $sub->save();
                Users::where('id', $creator->id)->where('subscriber_count', '>', 0)->decrement('subscriber_count');
                $expired++;
                continue;
            }

            // Renew: deduct coins and extend
            DB::transaction(function () use ($subscriber, $creator, $sub, $tier) {
                $subscriber->coin_wallet -= $tier->price_coins;
                $subscriber->save();

                $creator->coin_wallet += $tier->price_coins;
                $creator->coin_collected_lifetime += $tier->price_coins;
                $creator->save();

                $sub->started_at = now();
                $sub->expires_at = now()->addMonth();
                $sub->save();

                CoinTransaction::create([
                    'user_id' => $subscriber->id,
                    'type' => Constants::txnSubscriptionSent,
                    'coins' => $tier->price_coins,
                    'direction' => Constants::debit,
                    'related_user_id' => $creator->id,
                    'reference_id' => $sub->id,
                    'note' => "Auto-renewal: {$creator->fullname} ({$tier->name})",
                ]);
                CoinTransaction::create([
                    'user_id' => $creator->id,
                    'type' => Constants::txnSubscriptionReceived,
                    'coins' => $tier->price_coins,
                    'direction' => Constants::credit,
                    'related_user_id' => $subscriber->id,
                    'reference_id' => $sub->id,
                    'note' => "Auto-renewal from {$subscriber->fullname} ({$tier->name})",
                ]);
            });

            $renewed++;
        }

        // Also expire cancelled subscriptions past their expires_at
        CreatorSubscription::where('status', CreatorSubscription::STATUS_CANCELLED)
            ->where('expires_at', '<=', now())
            ->update(['status' => CreatorSubscription::STATUS_EXPIRED]);

        return ['renewed' => $renewed, 'expired' => $expired];
    }
}
