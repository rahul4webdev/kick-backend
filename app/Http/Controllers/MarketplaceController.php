<?php

namespace App\Http\Controllers;

use App\Models\CoinTransaction;
use App\Models\Constants;
use App\Models\GlobalFunction;
use App\Models\MarketplaceCampaign;
use App\Models\MarketplaceProposal;
use App\Models\Users;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Validator;

class MarketplaceController extends Controller
{
    // ─── Campaign CRUD (Brand) ──────────────────────────────────

    public function createCampaign(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);

        if ($user->account_type < 2) {
            return GlobalFunction::sendDataResponse(false, 'Business account required to create campaigns');
        }

        $rules = [
            'title' => 'required|string|max:200',
            'budget_coins' => 'required|integer|min:0',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $campaign = new MarketplaceCampaign();
        $campaign->brand_user_id = $user->id;
        $campaign->title = $request->title;
        $campaign->description = $request->description;
        $campaign->category = $request->category;
        $campaign->budget_coins = $request->budget_coins;
        $campaign->min_followers = $request->min_followers ?? 0;
        $campaign->min_posts = $request->min_posts ?? 0;
        $campaign->content_type = $request->content_type;
        $campaign->requirements = $request->requirements;
        $campaign->max_creators = $request->max_creators ?? 0;
        $campaign->status = MarketplaceCampaign::STATUS_ACTIVE;

        if ($request->has('deadline') && $request->deadline) {
            $campaign->deadline = $request->deadline;
        }

        if ($request->has('cover_image') && $request->cover_image) {
            $campaign->cover_image = $request->cover_image;
        }

        $campaign->save();

        $campaign->load(['brand:' . Constants::userPublicFields]);

        return GlobalFunction::sendDataResponse(true, 'Campaign created', $campaign);
    }

    public function updateCampaign(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);

        $campaign = MarketplaceCampaign::find($request->campaign_id);
        if (!$campaign || $campaign->brand_user_id != $user->id) {
            return GlobalFunction::sendDataResponse(false, 'Campaign not found or not yours');
        }

        if ($request->has('title')) $campaign->title = $request->title;
        if ($request->has('description')) $campaign->description = $request->description;
        if ($request->has('category')) $campaign->category = $request->category;
        if ($request->has('budget_coins')) $campaign->budget_coins = $request->budget_coins;
        if ($request->has('min_followers')) $campaign->min_followers = $request->min_followers;
        if ($request->has('min_posts')) $campaign->min_posts = $request->min_posts;
        if ($request->has('content_type')) $campaign->content_type = $request->content_type;
        if ($request->has('requirements')) $campaign->requirements = $request->requirements;
        if ($request->has('max_creators')) $campaign->max_creators = $request->max_creators;
        if ($request->has('status')) $campaign->status = $request->status;
        if ($request->has('deadline')) $campaign->deadline = $request->deadline;
        if ($request->has('cover_image')) $campaign->cover_image = $request->cover_image;

        $campaign->save();

        return GlobalFunction::sendDataResponse(true, 'Campaign updated', $campaign);
    }

    public function deleteCampaign(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);

        $campaign = MarketplaceCampaign::find($request->campaign_id);
        if (!$campaign || $campaign->brand_user_id != $user->id) {
            return GlobalFunction::sendDataResponse(false, 'Campaign not found or not yours');
        }

        $campaign->delete();

        return GlobalFunction::sendDataResponse(true, 'Campaign deleted');
    }

    // ─── Campaign Browse ────────────────────────────────────────

    public function fetchCampaigns(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);

        $query = MarketplaceCampaign::where('status', MarketplaceCampaign::STATUS_ACTIVE)
            ->with(['brand:' . Constants::userPublicFields]);

        if ($request->has('category') && $request->category) {
            $query->where('category', $request->category);
        }

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'ILIKE', "%{$search}%")
                  ->orWhere('description', 'ILIKE', "%{$search}%");
            });
        }

        if ($request->has('last_item_id') && $request->last_item_id) {
            $query->where('id', '<', $request->last_item_id);
        }

        $limit = $request->limit ?? 20;
        $campaigns = $query->orderBy('id', 'desc')->limit($limit)->get();

        // Add cover image URLs
        foreach ($campaigns as $c) {
            if ($c->cover_image) {
                $c->cover_image_url = GlobalFunction::generateFileUrl($c->cover_image);
            }
            // Check if current user already applied
            $c->has_applied = MarketplaceProposal::where('campaign_id', $c->id)
                ->where('creator_user_id', $user->id)
                ->exists();
        }

        return GlobalFunction::sendDataResponse(true, 'Campaigns', $campaigns);
    }

    public function fetchMyCampaigns(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);

        $campaigns = MarketplaceCampaign::where('brand_user_id', $user->id)
            ->with(['brand:' . Constants::userPublicFields])
            ->orderBy('id', 'desc')
            ->get();

        foreach ($campaigns as $c) {
            if ($c->cover_image) {
                $c->cover_image_url = GlobalFunction::generateFileUrl($c->cover_image);
            }
        }

        return GlobalFunction::sendDataResponse(true, 'My campaigns', $campaigns);
    }

    public function fetchCampaignById(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);

        $campaign = MarketplaceCampaign::where('id', $request->campaign_id)
            ->with(['brand:' . Constants::userPublicFields])
            ->first();

        if (!$campaign) {
            return GlobalFunction::sendDataResponse(false, 'Campaign not found');
        }

        if ($campaign->cover_image) {
            $campaign->cover_image_url = GlobalFunction::generateFileUrl($campaign->cover_image);
        }

        $campaign->has_applied = MarketplaceProposal::where('campaign_id', $campaign->id)
            ->where('creator_user_id', $user->id)
            ->exists();

        // If brand is viewing their own campaign, include proposals
        if ($campaign->brand_user_id == $user->id) {
            $campaign->proposals = MarketplaceProposal::where('campaign_id', $campaign->id)
                ->with(['creator:' . Constants::userPublicFields])
                ->orderBy('id', 'desc')
                ->get();
        }

        return GlobalFunction::sendDataResponse(true, 'Campaign detail', $campaign);
    }

    // ─── Proposals ──────────────────────────────────────────────

    public function applyToCampaign(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);

        $campaign = MarketplaceCampaign::find($request->campaign_id);
        if (!$campaign || $campaign->status != MarketplaceCampaign::STATUS_ACTIVE) {
            return GlobalFunction::sendDataResponse(false, 'Campaign not found or not active');
        }

        if ($campaign->brand_user_id == $user->id) {
            return GlobalFunction::sendDataResponse(false, 'Cannot apply to your own campaign');
        }

        // Check eligibility
        if ($campaign->min_followers > 0 && $user->follower_count < $campaign->min_followers) {
            return GlobalFunction::sendDataResponse(false, "Minimum {$campaign->min_followers} followers required");
        }

        // Check if already applied
        $existing = MarketplaceProposal::where('campaign_id', $campaign->id)
            ->where('creator_user_id', $user->id)
            ->first();
        if ($existing) {
            return GlobalFunction::sendDataResponse(false, 'Already applied to this campaign');
        }

        // Check max creators
        if ($campaign->max_creators > 0 && $campaign->accepted_count >= $campaign->max_creators) {
            return GlobalFunction::sendDataResponse(false, 'Campaign has reached maximum creators');
        }

        $proposal = new MarketplaceProposal();
        $proposal->campaign_id = $campaign->id;
        $proposal->brand_user_id = $campaign->brand_user_id;
        $proposal->creator_user_id = $user->id;
        $proposal->initiated_by = MarketplaceProposal::INITIATED_BY_CREATOR;
        $proposal->message = $request->message;
        $proposal->offered_coins = $request->offered_coins ?? $campaign->budget_coins;
        $proposal->status = MarketplaceProposal::STATUS_PENDING;
        $proposal->save();

        $campaign->increment('application_count');

        return GlobalFunction::sendDataResponse(true, 'Application submitted');
    }

    public function inviteCreator(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);

        $campaign = MarketplaceCampaign::find($request->campaign_id);
        if (!$campaign || $campaign->brand_user_id != $user->id) {
            return GlobalFunction::sendDataResponse(false, 'Campaign not found or not yours');
        }

        $creator = Users::find($request->creator_user_id);
        if (!$creator) {
            return GlobalFunction::sendDataResponse(false, 'Creator not found');
        }

        $existing = MarketplaceProposal::where('campaign_id', $campaign->id)
            ->where('creator_user_id', $creator->id)
            ->first();
        if ($existing) {
            return GlobalFunction::sendDataResponse(false, 'Already invited or applied');
        }

        $proposal = new MarketplaceProposal();
        $proposal->campaign_id = $campaign->id;
        $proposal->brand_user_id = $user->id;
        $proposal->creator_user_id = $creator->id;
        $proposal->initiated_by = MarketplaceProposal::INITIATED_BY_BRAND;
        $proposal->message = $request->message;
        $proposal->offered_coins = $request->offered_coins ?? $campaign->budget_coins;
        $proposal->status = MarketplaceProposal::STATUS_PENDING;
        $proposal->save();

        $campaign->increment('application_count');

        return GlobalFunction::sendDataResponse(true, 'Invitation sent');
    }

    public function respondToProposal(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);

        $proposal = MarketplaceProposal::find($request->proposal_id);
        if (!$proposal) {
            return GlobalFunction::sendDataResponse(false, 'Proposal not found');
        }

        // Brand can accept/decline creator applications
        // Creator can accept/decline brand invitations
        $isAuthorized = ($proposal->brand_user_id == $user->id && $proposal->initiated_by == MarketplaceProposal::INITIATED_BY_CREATOR)
            || ($proposal->creator_user_id == $user->id && $proposal->initiated_by == MarketplaceProposal::INITIATED_BY_BRAND);

        if (!$isAuthorized) {
            return GlobalFunction::sendDataResponse(false, 'Not authorized to respond');
        }

        $action = $request->action; // accept, decline
        if ($action == 'accept') {
            $proposal->status = MarketplaceProposal::STATUS_ACCEPTED;
            $proposal->save();

            $campaign = MarketplaceCampaign::find($proposal->campaign_id);
            if ($campaign) {
                $campaign->increment('accepted_count');
            }
        } elseif ($action == 'decline') {
            $proposal->status = MarketplaceProposal::STATUS_DECLINED;
            $proposal->save();
        } else {
            return GlobalFunction::sendDataResponse(false, 'Invalid action. Use accept or decline');
        }

        return GlobalFunction::sendDataResponse(true, 'Proposal ' . $action . 'ed');
    }

    public function completeProposal(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);

        $proposal = MarketplaceProposal::find($request->proposal_id);
        if (!$proposal || $proposal->status != MarketplaceProposal::STATUS_ACCEPTED) {
            return GlobalFunction::sendDataResponse(false, 'Proposal not found or not accepted');
        }

        if ($proposal->brand_user_id != $user->id) {
            return GlobalFunction::sendDataResponse(false, 'Only brand can mark as completed');
        }

        if ($request->has('deliverable_post_id')) {
            $proposal->deliverable_post_id = $request->deliverable_post_id;
        }

        $proposal->status = MarketplaceProposal::STATUS_COMPLETED;
        $proposal->save();

        // Pay the creator
        if ($proposal->offered_coins > 0) {
            DB::transaction(function () use ($proposal) {
                $brand = Users::find($proposal->brand_user_id);
                $creator = Users::find($proposal->creator_user_id);

                if ($brand && $creator && $brand->coin_collected_lifetime >= $proposal->offered_coins) {
                    $brand->decrement('coin_collected_lifetime', $proposal->offered_coins);
                    $creator->increment('coin_collected_lifetime', $proposal->offered_coins);

                    CoinTransaction::create([
                        'user_id' => $brand->id,
                        'type' => Constants::txnMarketplacePayout,
                        'coins' => $proposal->offered_coins,
                        'direction' => Constants::debit,
                        'related_user_id' => $creator->id,
                    ]);

                    CoinTransaction::create([
                        'user_id' => $creator->id,
                        'type' => Constants::txnMarketplaceEarning,
                        'coins' => $proposal->offered_coins,
                        'direction' => Constants::credit,
                        'related_user_id' => $brand->id,
                    ]);
                }
            });
        }

        return GlobalFunction::sendDataResponse(true, 'Proposal completed and creator paid');
    }

    public function fetchMyProposals(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);

        $query = MarketplaceProposal::where('creator_user_id', $user->id)
            ->with([
                'campaign',
                'brand:' . Constants::userPublicFields,
            ]);

        if ($request->has('status') && $request->status !== null) {
            $query->where('status', $request->status);
        }

        $proposals = $query->orderBy('id', 'desc')->get();

        foreach ($proposals as $p) {
            if ($p->campaign && $p->campaign->cover_image) {
                $p->campaign->cover_image_url = GlobalFunction::generateFileUrl($p->campaign->cover_image);
            }
        }

        return GlobalFunction::sendDataResponse(true, 'My proposals', $proposals);
    }

    public function fetchCampaignProposals(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);

        $campaign = MarketplaceCampaign::find($request->campaign_id);
        if (!$campaign || $campaign->brand_user_id != $user->id) {
            return GlobalFunction::sendDataResponse(false, 'Campaign not found or not yours');
        }

        $proposals = MarketplaceProposal::where('campaign_id', $campaign->id)
            ->with(['creator:' . Constants::userPublicFields])
            ->orderBy('id', 'desc')
            ->get();

        return GlobalFunction::sendDataResponse(true, 'Campaign proposals', $proposals);
    }

    // ─── Admin ──────────────────────────────────────────────────

    public function marketplaceAdmin()
    {
        return view('marketplace');
    }

    public function listCampaigns_Admin(Request $request)
    {
        $totalData = MarketplaceCampaign::count();

        $limit = $request->input('length');
        $start = $request->input('start');
        $columnIndex = $request->input('order.0.column');
        $dir = $request->input('order.0.dir') ?? 'desc';
        $search = $request->input('search.value');

        $query = MarketplaceCampaign::with(['brand:id,username,fullname,profile_photo']);

        if ($request->has('status_filter') && $request->status_filter !== '') {
            $query->where('status', $request->status_filter);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'ILIKE', "%{$search}%")
                  ->orWhereHas('brand', function ($q2) use ($search) {
                      $q2->where('username', 'ILIKE', "%{$search}%");
                  });
            });
            $totalFiltered = $query->count();
        } else {
            $totalFiltered = $totalData;
        }

        $campaigns = $query->offset($start)->limit($limit)->orderBy('id', $dir)->get();

        $data = [];
        foreach ($campaigns as $i => $c) {
            $brandName = $c->brand ? $c->brand->username : 'N/A';
            $statusBadge = match ($c->status) {
                1 => '<span class="badge bg-secondary">Draft</span>',
                2 => '<span class="badge bg-success">Active</span>',
                3 => '<span class="badge bg-warning">Paused</span>',
                4 => '<span class="badge bg-info">Completed</span>',
                5 => '<span class="badge bg-danger">Cancelled</span>',
                default => '<span class="badge bg-secondary">Unknown</span>',
            };

            $actions = '<a href="javascript:void(0)" class="delete text-danger" rel="' . $c->id . '"><i class="ri-delete-bin-line"></i></a>';

            $data[] = [
                $start + $i + 1,
                $c->title,
                $brandName,
                $c->category ?? '-',
                number_format($c->budget_coins),
                $c->application_count,
                $c->accepted_count,
                $statusBadge,
                $c->created_at?->format('M d, Y'),
                $actions,
            ];
        }

        return response()->json([
            "draw" => intval($request->input('draw')),
            "recordsTotal" => intval($totalData),
            "recordsFiltered" => intval($totalFiltered),
            "data" => $data,
        ]);
    }

    public function deleteCampaign_Admin(Request $request)
    {
        $campaign = MarketplaceCampaign::find($request->id);
        if ($campaign) {
            $campaign->delete();
            return response()->json(['status' => true, 'message' => 'Campaign deleted']);
        }
        return response()->json(['status' => false, 'message' => 'Campaign not found']);
    }
}
