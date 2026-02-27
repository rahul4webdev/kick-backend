<?php

namespace App\Http\Controllers;

use App\Models\Constants;
use App\Models\GlobalFunction;
use App\Models\Posts;
use App\Models\Series;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SeriesController extends Controller
{
    // ─── API Endpoints ──────────────────────────────────────────

    public function fetchSeries(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => 'This user is frozen!'];
        }

        $rules = ['limit' => 'required'];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $query = Series::where('is_active', true)
            ->where('status', Series::STATUS_APPROVED)
            ->orderByDesc('id')
            ->limit($request->limit);

        if ($request->has('last_item_id')) {
            $query->where('id', '<', $request->last_item_id);
        }

        if ($request->has('genre') && !empty($request->genre)) {
            $query->where('genre', $request->genre);
        }

        if ($request->has('language') && !empty($request->language)) {
            $query->where('language', $request->language);
        }

        // Following filter — only series from users the current user follows
        if ($request->has('sub_tab') && $request->sub_tab === 'following') {
            $followingIds = GlobalFunction::getFollowingUserIds($user->id);
            $query->whereIn('user_id', $followingIds);
        }

        // Trending — order by total_views
        if ($request->has('sub_tab') && $request->sub_tab === 'trending') {
            $query->reorder()->orderByDesc('total_views');
        }

        $series = $query->get();

        // Append user data
        $series->each(function ($s) {
            $s->user = $s->user()->first(['id', 'username', 'profile_photo']);
        });

        return GlobalFunction::sendDataResponse(true, 'Series fetched successfully', $series);
    }

    public function fetchSeriesEpisodes(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => 'This user is frozen!'];
        }

        $rules = [
            'series_id' => 'required',
            'limit' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $series = Series::find($request->series_id);
        if (!$series || !$series->is_active || $series->status != Series::STATUS_APPROVED) {
            return GlobalFunction::sendSimpleResponse(false, 'Series not found');
        }

        $query = Posts::where('content_type', Constants::contentTypeShortStory)
            ->whereRaw("(content_metadata->>'series_id')::int = ?", [$request->series_id])
            ->with(Constants::postsWithArray)
            ->orderByRaw("(content_metadata->>'episode_number')::int ASC")
            ->limit($request->limit);

        if ($request->has('last_item_id')) {
            $query->where('id', '<', $request->last_item_id);
        }

        $episodes = $query->get();

        $processedData = GlobalFunction::processPostsListData($user, $episodes);

        return GlobalFunction::sendDataResponse(true, 'Episodes fetched successfully', $processedData);
    }

    public function createSeries(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => 'This user is frozen!'];
        }

        // Validate account type
        $validation = GlobalFunction::validateContentTypeUpload($user, Constants::contentTypeShortStory);
        if (!$validation['status']) {
            return GlobalFunction::sendSimpleResponse(false, $validation['message']);
        }

        $rules = [
            'title' => 'required|string|max:255',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $series = new Series();
        $series->user_id = $user->id;
        $series->title = $request->title;
        $series->description = $request->description;
        $series->genre = $request->genre;
        $series->language = $request->language;
        $series->status = Series::STATUS_PENDING;

        if ($request->hasFile('cover_image')) {
            $series->cover_image = GlobalFunction::saveFileAndGivePath($request->cover_image);
        }

        $series->save();

        return GlobalFunction::sendSimpleResponse(true, 'Series created and submitted for approval');
    }

    // ─── Admin Endpoints ────────────────────────────────────────

    public function seriesAdmin()
    {
        return view('series');
    }

    public function listSeries_Admin(Request $request)
    {
        $query = Series::query();

        // Filter by status tab
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
                  ->orWhere('genre', 'LIKE', "%{$searchValue}%");
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

            $ownerName = $item->user ? $item->user->username : 'Unknown';

            $statusLabel = match ($item->status) {
                Series::STATUS_PENDING => "<span class='badge bg-warning'>Pending</span>",
                Series::STATUS_APPROVED => "<span class='badge bg-success'>Approved</span>",
                Series::STATUS_REJECTED => "<span class='badge bg-danger'>Rejected</span>",
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
                htmlspecialchars($item->genre ?? '-'),
                htmlspecialchars($item->language ?? '-'),
                $item->episode_count,
                $item->total_views,
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

    public function updateSeriesStatus(Request $request)
    {
        $series = Series::find($request->id);
        if (!$series) {
            return GlobalFunction::sendSimpleResponse(false, 'Series not found');
        }

        $series->status = $request->status;
        $series->save();

        $statusText = match ((int)$request->status) {
            Series::STATUS_APPROVED => 'approved',
            Series::STATUS_REJECTED => 'rejected',
            default => 'updated',
        };

        return GlobalFunction::sendSimpleResponse(true, "Series {$statusText} successfully");
    }

    public function deleteSeries_Admin(Request $request)
    {
        $series = Series::find($request->id);
        if (!$series) {
            return GlobalFunction::sendSimpleResponse(false, 'Series not found');
        }

        if ($series->cover_image) {
            GlobalFunction::deleteFile($series->cover_image);
        }

        $series->delete();

        return GlobalFunction::sendSimpleResponse(true, 'Series deleted successfully');
    }
}
