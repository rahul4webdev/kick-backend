<?php

namespace App\Http\Controllers;

use App\Models\CoinTransaction;
use App\Models\Constants;
use App\Models\GlobalFunction;
use App\Models\PaidSeries;
use App\Models\PaidSeriesPurchase;
use App\Models\PaidSeriesVideo;
use App\Models\Posts;
use App\Models\Users;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PaidSeriesController extends Controller
{
    // ─── API Endpoints ──────────────────────────────────────────

    public function createPaidSeries(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => 'This user is frozen!'];
        }

        $rules = [
            'title' => 'required|string|max:255',
            'price_coins' => 'required|integer|min:1',
            'description' => 'nullable|string|max:2000',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return GlobalFunction::sendSimpleResponse(false, $validator->errors()->first());
        }

        $series = new PaidSeries();
        $series->creator_id = $user->id;
        $series->title = $request->title;
        $series->description = $request->description;
        $series->price_coins = $request->price_coins;
        $series->status = PaidSeries::STATUS_PENDING;

        if ($request->hasFile('cover_image')) {
            $series->cover_image = GlobalFunction::saveFileAndGivePath($request->cover_image);
        }

        $series->save();

        return GlobalFunction::sendDataResponse(true, 'Paid series created and submitted for approval', $series);
    }

    public function updatePaidSeries(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => 'This user is frozen!'];
        }

        $series = PaidSeries::where('id', $request->series_id)
            ->where('creator_id', $user->id)
            ->first();
        if (!$series) {
            return GlobalFunction::sendSimpleResponse(false, 'Series not found');
        }

        if ($request->has('title')) $series->title = $request->title;
        if ($request->has('description')) $series->description = $request->description;
        if ($request->has('price_coins')) $series->price_coins = $request->price_coins;

        if ($request->hasFile('cover_image')) {
            if ($series->cover_image) {
                GlobalFunction::deleteFile($series->cover_image);
            }
            $series->cover_image = GlobalFunction::saveFileAndGivePath($request->cover_image);
        }

        $series->save();

        return GlobalFunction::sendDataResponse(true, 'Series updated', $series);
    }

    public function deletePaidSeries(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => 'This user is frozen!'];
        }

        $series = PaidSeries::where('id', $request->series_id)
            ->where('creator_id', $user->id)
            ->first();
        if (!$series) {
            return GlobalFunction::sendSimpleResponse(false, 'Series not found');
        }

        if ($series->cover_image) {
            GlobalFunction::deleteFile($series->cover_image);
        }

        $series->delete();

        return GlobalFunction::sendSimpleResponse(true, 'Series deleted');
    }

    public function addVideoToSeries(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => 'This user is frozen!'];
        }

        $rules = [
            'series_id' => 'required',
            'post_id' => 'required',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return GlobalFunction::sendSimpleResponse(false, $validator->errors()->first());
        }

        $series = PaidSeries::where('id', $request->series_id)
            ->where('creator_id', $user->id)
            ->first();
        if (!$series) {
            return GlobalFunction::sendSimpleResponse(false, 'Series not found');
        }

        // Verify the post belongs to this user
        $post = Posts::where('id', $request->post_id)
            ->where('user_id', $user->id)
            ->first();
        if (!$post) {
            return GlobalFunction::sendSimpleResponse(false, 'Post not found');
        }

        // Check if already in series
        $exists = PaidSeriesVideo::where('series_id', $series->id)
            ->where('post_id', $post->id)
            ->exists();
        if ($exists) {
            return GlobalFunction::sendSimpleResponse(false, 'Video already in this series');
        }

        $maxPosition = PaidSeriesVideo::where('series_id', $series->id)->max('position') ?? -1;

        PaidSeriesVideo::create([
            'series_id' => $series->id,
            'post_id' => $post->id,
            'position' => $maxPosition + 1,
        ]);

        $series->video_count = PaidSeriesVideo::where('series_id', $series->id)->count();
        $series->save();

        return GlobalFunction::sendSimpleResponse(true, 'Video added to series');
    }

    public function removeVideoFromSeries(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => 'This user is frozen!'];
        }

        $series = PaidSeries::where('id', $request->series_id)
            ->where('creator_id', $user->id)
            ->first();
        if (!$series) {
            return GlobalFunction::sendSimpleResponse(false, 'Series not found');
        }

        PaidSeriesVideo::where('series_id', $series->id)
            ->where('post_id', $request->post_id)
            ->delete();

        $series->video_count = PaidSeriesVideo::where('series_id', $series->id)->count();
        $series->save();

        return GlobalFunction::sendSimpleResponse(true, 'Video removed from series');
    }

    public function reorderSeriesVideos(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => 'This user is frozen!'];
        }

        $series = PaidSeries::where('id', $request->series_id)
            ->where('creator_id', $user->id)
            ->first();
        if (!$series) {
            return GlobalFunction::sendSimpleResponse(false, 'Series not found');
        }

        $postIds = $request->post_ids; // array of post IDs in desired order
        if (!is_array($postIds)) {
            return GlobalFunction::sendSimpleResponse(false, 'post_ids must be an array');
        }

        foreach ($postIds as $index => $postId) {
            PaidSeriesVideo::where('series_id', $series->id)
                ->where('post_id', $postId)
                ->update(['position' => $index]);
        }

        return GlobalFunction::sendSimpleResponse(true, 'Videos reordered');
    }

    public function fetchPaidSeries(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => 'This user is frozen!'];
        }

        $limit = $request->limit ?? 20;

        $query = PaidSeries::where('is_active', true)
            ->where('status', PaidSeries::STATUS_APPROVED)
            ->orderByDesc('id')
            ->limit($limit);

        if ($request->has('last_item_id') && $request->last_item_id) {
            $query->where('id', '<', $request->last_item_id);
        }

        if ($request->has('creator_id') && $request->creator_id) {
            $query->where('creator_id', $request->creator_id);
        }

        $series = $query->get();

        // Append creator data and purchase status
        $series->each(function ($s) use ($user) {
            $s->creator = $s->creator()->first(['id', 'username', 'fullname', 'profile_photo', 'is_verify']);
            $s->is_purchased = PaidSeriesPurchase::where('series_id', $s->id)
                ->where('user_id', $user->id)
                ->exists();
            $s->cover_image_url = $s->cover_image ? GlobalFunction::generateFileUrl($s->cover_image) : null;
        });

        return GlobalFunction::sendDataResponse(true, 'Paid series fetched', $series);
    }

    public function fetchMyPaidSeries(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => 'This user is frozen!'];
        }

        $series = PaidSeries::where('creator_id', $user->id)
            ->orderByDesc('id')
            ->get();

        $series->each(function ($s) {
            $s->cover_image_url = $s->cover_image ? GlobalFunction::generateFileUrl($s->cover_image) : null;
        });

        return GlobalFunction::sendDataResponse(true, 'My paid series fetched', $series);
    }

    public function fetchSeriesVideos(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => 'This user is frozen!'];
        }

        $series = PaidSeries::find($request->series_id);
        if (!$series || !$series->is_active || $series->status != PaidSeries::STATUS_APPROVED) {
            return GlobalFunction::sendSimpleResponse(false, 'Series not found');
        }

        // Check if user has purchased or is the creator
        $isPurchased = $series->creator_id == $user->id ||
            PaidSeriesPurchase::where('series_id', $series->id)
                ->where('user_id', $user->id)
                ->exists();

        if (!$isPurchased) {
            // Return series info + video count but no actual video data
            $series->creator = $series->creator()->first(['id', 'username', 'fullname', 'profile_photo', 'is_verify']);
            $series->is_purchased = false;
            $series->cover_image_url = $series->cover_image ? GlobalFunction::generateFileUrl($series->cover_image) : null;
            return [
                'status' => true,
                'message' => 'Purchase required',
                'data' => [
                    'series' => $series,
                    'videos' => [],
                    'is_purchased' => false,
                ],
            ];
        }

        // Get video posts
        $videoIds = PaidSeriesVideo::where('series_id', $series->id)
            ->orderBy('position')
            ->pluck('post_id');

        $posts = Posts::whereIn('id', $videoIds)
            ->with(Constants::postsWithArray)
            ->get();

        // Sort by the series video order
        $orderedPosts = $videoIds->map(fn($id) => $posts->firstWhere('id', $id))->filter();

        $processedData = GlobalFunction::processPostsListData($user, $orderedPosts->values());

        $series->creator = $series->creator()->first(['id', 'username', 'fullname', 'profile_photo', 'is_verify']);
        $series->is_purchased = true;
        $series->cover_image_url = $series->cover_image ? GlobalFunction::generateFileUrl($series->cover_image) : null;

        return [
            'status' => true,
            'message' => 'Videos fetched',
            'data' => [
                'series' => $series,
                'videos' => $processedData,
                'is_purchased' => true,
            ],
        ];
    }

    public function purchaseSeries(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => 'This user is frozen!'];
        }

        $series = PaidSeries::where('id', $request->series_id)
            ->where('is_active', true)
            ->where('status', PaidSeries::STATUS_APPROVED)
            ->first();
        if (!$series) {
            return GlobalFunction::sendSimpleResponse(false, 'Series not found');
        }

        // Can't purchase own series
        if ($series->creator_id == $user->id) {
            return GlobalFunction::sendSimpleResponse(false, 'You cannot purchase your own series');
        }

        // Check if already purchased
        $existing = PaidSeriesPurchase::where('series_id', $series->id)
            ->where('user_id', $user->id)
            ->first();
        if ($existing) {
            return GlobalFunction::sendSimpleResponse(false, 'Already purchased');
        }

        // Check coin balance
        if ($user->coin_wallet < $series->price_coins) {
            return GlobalFunction::sendSimpleResponse(false, 'Insufficient coins');
        }

        return DB::transaction(function () use ($user, $series) {
            // Deduct coins from buyer
            $user->coin_wallet -= $series->price_coins;
            $user->save();

            // Credit coins to creator
            $creator = Users::find($series->creator_id);
            $creator->coin_wallet += $series->price_coins;
            $creator->coin_collected_lifetime += $series->price_coins;
            $creator->save();

            // Create purchase record
            $purchase = PaidSeriesPurchase::create([
                'series_id' => $series->id,
                'user_id' => $user->id,
                'amount_coins' => $series->price_coins,
                'purchased_at' => now(),
            ]);

            // Create coin transactions
            $txnDebit = CoinTransaction::create([
                'user_id' => $user->id,
                'type' => Constants::txnPaidSeriesPurchase,
                'coins' => $series->price_coins,
                'direction' => Constants::debit,
                'related_user_id' => $series->creator_id,
                'reference_id' => $purchase->id,
                'note' => "Purchased series: {$series->title}",
            ]);

            CoinTransaction::create([
                'user_id' => $series->creator_id,
                'type' => Constants::txnPaidSeriesRevenue,
                'coins' => $series->price_coins,
                'direction' => Constants::credit,
                'related_user_id' => $user->id,
                'reference_id' => $purchase->id,
                'note' => "Series sale: {$series->title} by {$user->fullname}",
            ]);

            $purchase->transaction_id = $txnDebit->id;
            $purchase->save();

            // Update series stats
            $series->purchase_count += 1;
            $series->total_revenue += $series->price_coins;
            $series->save();

            return [
                'status' => true,
                'message' => 'Series purchased successfully',
                'data' => [
                    'purchase_id' => $purchase->id,
                    'coins_remaining' => $user->coin_wallet,
                ],
            ];
        });
    }

    public function fetchMyPurchases(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => 'This user is frozen!'];
        }

        $limit = $request->limit ?? 20;

        $query = PaidSeriesPurchase::where('user_id', $user->id)
            ->with(['series.creator:id,username,fullname,profile_photo,is_verify'])
            ->orderByDesc('id')
            ->limit($limit);

        if ($request->has('last_item_id') && $request->last_item_id) {
            $query->where('id', '<', $request->last_item_id);
        }

        $purchases = $query->get();

        $purchases->each(function ($p) {
            if ($p->series && $p->series->cover_image) {
                $p->series->cover_image_url = GlobalFunction::generateFileUrl($p->series->cover_image);
            }
        });

        return GlobalFunction::sendDataResponse(true, 'Purchases fetched', $purchases);
    }

    // ─── Admin Endpoints ────────────────────────────────────────

    public function paidSeriesAdmin()
    {
        return view('paid_series');
    }

    public function listPaidSeries_Admin(Request $request)
    {
        $query = PaidSeries::query();

        if ($request->has('status_filter') && $request->status_filter !== '') {
            $query->where('status', $request->status_filter);
        }

        $totalData = $query->count();

        $limit = $request->input('length');
        $start = $request->input('start');
        $searchValue = $request->input('search.value');

        if (!empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('title', 'LIKE', "%{$searchValue}%")
                  ->orWhereHas('creator', function ($uq) use ($searchValue) {
                      $uq->where('username', 'LIKE', "%{$searchValue}%");
                  });
            });
        }
        $totalFiltered = $query->count();

        $result = $query->offset($start)
                        ->limit($limit)
                        ->orderBy('id', 'DESC')
                        ->get();

        $data = $result->map(function ($item) {
            $coverUrl = $item->cover_image ? GlobalFunction::generateFileUrl($item->cover_image) : url('assets/img/placeholder.png');
            $cover = "<img class='rounded' width='60' height='80' src='{$coverUrl}' alt=''>";

            $ownerName = $item->creator ? $item->creator->username : 'Unknown';

            $statusLabel = match ($item->status) {
                PaidSeries::STATUS_PENDING => "<span class='badge bg-warning'>Pending</span>",
                PaidSeries::STATUS_APPROVED => "<span class='badge bg-success'>Approved</span>",
                PaidSeries::STATUS_REJECTED => "<span class='badge bg-danger'>Rejected</span>",
                default => "<span class='badge bg-secondary'>Unknown</span>",
            };

            $approve = "<a href='#'
                        rel='{$item->id}'
                        data-status='2'
                        class='action-btn update-status d-flex align-items-center justify-content-center btn border rounded-2 text-success ms-1'
                        title='Approve'>
                        <i class='uil-check'></i>
                        </a>";

            $reject = "<a href='#'
                        rel='{$item->id}'
                        data-status='3'
                        class='action-btn update-status d-flex align-items-center justify-content-center btn border rounded-2 text-warning ms-1'
                        title='Reject'>
                        <i class='uil-times'></i>
                        </a>";

            $delete = "<a href='#'
                          rel='{$item->id}'
                          class='action-btn delete d-flex align-items-center justify-content-center btn border rounded-2 text-danger ms-1'>
                            <i class='uil-trash-alt'></i>
                        </a>";

            $action = "<span class='d-flex justify-content-end align-items-center'>{$approve}{$reject}{$delete}</span>";

            return [
                $cover,
                htmlspecialchars($item->title),
                $ownerName,
                $item->price_coins . ' coins',
                $item->video_count,
                $item->purchase_count,
                $item->total_revenue . ' coins',
                $statusLabel,
                GlobalFunction::formateDatabaseTime($item->created_at),
                $action
            ];
        });

        return response()->json([
            "draw" => intval($request->input('draw')),
            "recordsTotal" => intval($totalData),
            "recordsFiltered" => intval($totalFiltered),
            "data" => $data,
        ]);
    }

    public function updatePaidSeriesStatus(Request $request)
    {
        $series = PaidSeries::find($request->id);
        if (!$series) {
            return GlobalFunction::sendSimpleResponse(false, 'Series not found');
        }

        $series->status = $request->status;
        $series->save();

        $statusText = match ((int)$request->status) {
            PaidSeries::STATUS_APPROVED => 'approved',
            PaidSeries::STATUS_REJECTED => 'rejected',
            default => 'updated',
        };

        return GlobalFunction::sendSimpleResponse(true, "Paid series {$statusText} successfully");
    }

    public function deletePaidSeries_Admin(Request $request)
    {
        $series = PaidSeries::find($request->id);
        if (!$series) {
            return GlobalFunction::sendSimpleResponse(false, 'Series not found');
        }

        if ($series->cover_image) {
            GlobalFunction::deleteFile($series->cover_image);
        }

        $series->delete();

        return GlobalFunction::sendSimpleResponse(true, 'Paid series deleted successfully');
    }
}
