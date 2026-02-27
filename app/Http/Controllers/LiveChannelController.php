<?php

namespace App\Http\Controllers;

use App\Models\Constants;
use App\Models\GlobalFunction;
use App\Models\LiveChannel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LiveChannelController extends Controller
{
    // ─── API Endpoints ──────────────────────────────────────────

    public function fetchLiveChannels(Request $request)
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

        $query = LiveChannel::where('is_active', true)
            ->orderByDesc('is_live')
            ->orderBy('sort_order', 'ASC')
            ->orderByDesc('viewer_count')
            ->limit($request->limit);

        if ($request->has('last_item_id')) {
            $query->where('id', '<', $request->last_item_id);
        }

        if ($request->has('category') && !empty($request->category)) {
            $query->where('category', $request->category);
        }

        if ($request->has('language') && !empty($request->language)) {
            $query->where('language', $request->language);
        }

        $channels = $query->get();

        return GlobalFunction::sendDataResponse(true, 'Live channels fetched successfully', $channels);
    }

    public function addLiveChannel(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => 'This user is frozen!'];
        }

        // Only News & Media accounts can add live channels
        if ($user->account_type != Constants::accountTypeNewsMedia) {
            return GlobalFunction::sendSimpleResponse(false, 'Only News & Media accounts can add live channels');
        }

        if ($user->business_status != Constants::businessStatusApproved) {
            return GlobalFunction::sendSimpleResponse(false, 'Your business account must be approved first');
        }

        $rules = [
            'channel_name' => 'required|string|max:200',
            'stream_url' => 'required|string|max:500',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $channel = new LiveChannel();
        $channel->user_id = $user->id;
        $channel->channel_name = $request->channel_name;
        $channel->stream_url = $request->stream_url;
        $channel->stream_type = $request->stream_type ?? 'hls';
        $channel->category = $request->category;
        $channel->language = $request->language;
        $channel->is_live = false;
        $channel->is_active = false; // Needs admin approval

        if ($request->hasFile('channel_logo')) {
            $channel->channel_logo = GlobalFunction::saveFileAndGivePath($request->channel_logo);
        }

        $channel->save();

        return GlobalFunction::sendSimpleResponse(true, 'Live channel submitted for approval');
    }

    public function updateLiveChannel(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => 'This user is frozen!'];
        }

        $channel = LiveChannel::where('id', $request->id)
            ->where('user_id', $user->id)
            ->first();

        if (!$channel) {
            return GlobalFunction::sendSimpleResponse(false, 'Channel not found or not owned by you');
        }

        if ($request->has('channel_name')) $channel->channel_name = $request->channel_name;
        if ($request->has('stream_url')) $channel->stream_url = $request->stream_url;
        if ($request->has('stream_type')) $channel->stream_type = $request->stream_type;
        if ($request->has('category')) $channel->category = $request->category;
        if ($request->has('language')) $channel->language = $request->language;
        if ($request->has('is_live')) $channel->is_live = $request->is_live;

        if ($request->hasFile('channel_logo')) {
            if ($channel->channel_logo) {
                GlobalFunction::deleteFile($channel->channel_logo);
            }
            $channel->channel_logo = GlobalFunction::saveFileAndGivePath($request->channel_logo);
        }

        $channel->save();

        return GlobalFunction::sendSimpleResponse(true, 'Channel updated successfully');
    }

    public function deleteLiveChannel(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);

        $channel = LiveChannel::where('id', $request->id)
            ->where('user_id', $user->id)
            ->first();

        if (!$channel) {
            return GlobalFunction::sendSimpleResponse(false, 'Channel not found or not owned by you');
        }

        if ($channel->channel_logo) {
            GlobalFunction::deleteFile($channel->channel_logo);
        }

        $channel->delete();

        return GlobalFunction::sendSimpleResponse(true, 'Channel deleted successfully');
    }

    // ─── Admin Endpoints ────────────────────────────────────────

    public function liveChannels()
    {
        return view('live_channels');
    }

    public function listLiveChannels_Admin(Request $request)
    {
        $query = LiveChannel::query();
        $totalData = $query->count();

        $limit = $request->input('length');
        $start = $request->input('start');
        $searchValue = $request->input('search.value');

        if (!empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('channel_name', 'LIKE', "%{$searchValue}%")
                  ->orWhere('category', 'LIKE', "%{$searchValue}%");
            });
        }
        $totalFiltered = $query->count();

        $result = $query->offset($start)
                        ->limit($limit)
                        ->orderBy('id', 'DESC')
                        ->get();

        $data = $result->map(function ($item) {
            $logoUrl = $item->channel_logo ? GlobalFunction::generateFileUrl($item->channel_logo) : url('assets/img/placeholder.png');
            $logo = "<img class='rounded' width='50' height='50' src='{$logoUrl}' alt=''>";

            $liveStatus = $item->is_live
                ? "<span class='badge bg-danger'>LIVE</span>"
                : "<span class='badge bg-secondary'>Offline</span>";

            $activeStatus = $item->is_active
                ? "<span class='badge bg-success'>Active</span>"
                : "<span class='badge bg-warning'>Inactive</span>";

            $streamType = strtoupper($item->stream_type ?? 'HLS');

            $ownerName = $item->user ? $item->user->username : 'Admin';

            $edit = "<a href='#'
                        rel='{$item->id}'
                        data-channel-name='" . htmlspecialchars($item->channel_name, ENT_QUOTES) . "'
                        data-stream-url='" . htmlspecialchars($item->stream_url, ENT_QUOTES) . "'
                        data-stream-type='{$item->stream_type}'
                        data-category='" . htmlspecialchars($item->category ?? '', ENT_QUOTES) . "'
                        data-language='" . htmlspecialchars($item->language ?? '', ENT_QUOTES) . "'
                        data-sort-order='{$item->sort_order}'
                        class='action-btn edit d-flex align-items-center justify-content-center btn border rounded-2 text-success ms-1'>
                        <i class='uil-pen'></i>
                        </a>";

            $toggleActive = "<a href='#'
                          rel='{$item->id}'
                          class='action-btn toggle-active d-flex align-items-center justify-content-center btn border rounded-2 text-info ms-1'
                          title='Toggle Active'>
                            <i class='uil-power'></i>
                        </a>";

            $delete = "<a href='#'
                          rel='{$item->id}'
                          class='action-btn delete d-flex align-items-center justify-content-center btn border rounded-2 text-danger ms-1'>
                            <i class='uil-trash-alt'></i>
                        </a>";

            $action = "<span class='d-flex justify-content-end align-items-center'>{$edit}{$toggleActive}{$delete}</span>";

            return [
                $logo,
                htmlspecialchars($item->channel_name),
                $ownerName,
                $streamType,
                htmlspecialchars($item->category ?? '-'),
                $liveStatus,
                $activeStatus,
                $item->viewer_count,
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

    public function toggleLiveChannelStatus(Request $request)
    {
        $channel = LiveChannel::find($request->id);
        if (!$channel) {
            return GlobalFunction::sendSimpleResponse(false, 'Channel not found');
        }
        $channel->is_active = !$channel->is_active;
        $channel->save();

        return GlobalFunction::sendSimpleResponse(true, $channel->is_active ? 'Channel activated' : 'Channel deactivated');
    }

    public function addLiveChannel_Admin(Request $request)
    {
        $channel = new LiveChannel();
        $channel->channel_name = $request->channel_name;
        $channel->stream_url = $request->stream_url;
        $channel->stream_type = $request->stream_type ?? 'hls';
        $channel->category = $request->category;
        $channel->language = $request->language;
        $channel->sort_order = $request->sort_order ?? 0;
        $channel->is_active = true;
        $channel->is_live = true;

        if ($request->hasFile('channel_logo')) {
            $channel->channel_logo = GlobalFunction::saveFileAndGivePath($request->channel_logo);
        }

        $channel->save();

        return GlobalFunction::sendSimpleResponse(true, 'Live channel added successfully');
    }

    public function editLiveChannel_Admin(Request $request)
    {
        $channel = LiveChannel::find($request->id);
        if (!$channel) {
            return GlobalFunction::sendSimpleResponse(false, 'Channel not found');
        }

        $channel->channel_name = $request->channel_name;
        $channel->stream_url = $request->stream_url;
        $channel->stream_type = $request->stream_type ?? $channel->stream_type;
        $channel->category = $request->category;
        $channel->language = $request->language;
        $channel->sort_order = $request->sort_order ?? 0;

        if ($request->hasFile('channel_logo')) {
            if ($channel->channel_logo) {
                GlobalFunction::deleteFile($channel->channel_logo);
            }
            $channel->channel_logo = GlobalFunction::saveFileAndGivePath($request->channel_logo);
        }

        $channel->save();

        return GlobalFunction::sendSimpleResponse(true, 'Channel updated successfully');
    }

    public function deleteLiveChannel_Admin(Request $request)
    {
        $channel = LiveChannel::find($request->id);
        if (!$channel) {
            return GlobalFunction::sendSimpleResponse(false, 'Channel not found');
        }

        if ($channel->channel_logo) {
            GlobalFunction::deleteFile($channel->channel_logo);
        }

        $channel->delete();

        return GlobalFunction::sendSimpleResponse(true, 'Channel deleted successfully');
    }
}
