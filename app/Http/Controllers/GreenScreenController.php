<?php

namespace App\Http\Controllers;

use App\Models\GlobalFunction;
use App\Models\GreenScreenBg;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class GreenScreenController extends Controller
{
    // ─── API Endpoints ──────────────────────────────────────────

    public function fetchBackgrounds(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => 'This user is frozen!'];
        }

        $query = GreenScreenBg::where('is_active', true)
            ->orderBy('sort_order', 'ASC')
            ->orderByDesc('id');

        if ($request->has('category') && !empty($request->category)) {
            $query->where('category', $request->category);
        }

        $backgrounds = $query->get();

        // Get distinct categories
        $categories = GreenScreenBg::where('is_active', true)
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->pluck('category')
            ->values();

        return [
            'status' => true,
            'message' => 'Backgrounds fetched successfully',
            'data' => $backgrounds,
            'categories' => $categories,
        ];
    }

    // ─── Admin Endpoints ────────────────────────────────────────

    public function greenScreenBgs()
    {
        return view('green_screen_bgs');
    }

    public function listGreenScreenBgs(Request $request)
    {
        $query = GreenScreenBg::query();
        $totalData = $query->count();

        $limit = $request->input('length');
        $start = $request->input('start');
        $searchValue = $request->input('search.value');

        if (!empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('title', 'LIKE', "%{$searchValue}%")
                  ->orWhere('category', 'LIKE', "%{$searchValue}%");
            });
        }
        $totalFiltered = $query->count();

        $result = $query->offset($start)
                        ->limit($limit)
                        ->orderBy('sort_order', 'ASC')
                        ->orderBy('id', 'DESC')
                        ->get();

        $data = $result->map(function ($item) {
            $thumbUrl = $item->image ? GlobalFunction::generateFileUrl($item->image) : url('assets/img/placeholder.png');
            $thumb = "<img class='rounded' width='50' height='50' src='{$thumbUrl}' alt=''>";

            $activeStatus = $item->is_active
                ? "<span class='badge bg-success'>Active</span>"
                : "<span class='badge bg-warning'>Inactive</span>";

            $typeBadge = $item->type === 'video'
                ? "<span class='badge bg-info'>Video</span>"
                : "<span class='badge bg-primary'>Image</span>";

            $edit = "<a href='#'
                        rel='{$item->id}'
                        data-title='" . htmlspecialchars($item->title, ENT_QUOTES) . "'
                        data-category='" . htmlspecialchars($item->category ?? '', ENT_QUOTES) . "'
                        data-type='{$item->type}'
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
                $thumb,
                htmlspecialchars($item->title),
                $typeBadge,
                htmlspecialchars($item->category ?? '-'),
                $item->sort_order,
                $activeStatus,
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

    public function addGreenScreenBg(Request $request)
    {
        $rules = [
            'title' => 'required|string|max:200',
            'type' => 'required|in:image,video',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $bg = new GreenScreenBg();
        $bg->title = $request->title;
        $bg->type = $request->type;
        $bg->category = $request->category;
        $bg->sort_order = $request->sort_order ?? 0;
        $bg->is_active = true;

        if ($request->hasFile('image')) {
            $bg->image = GlobalFunction::saveFileAndGivePath($request->image);
        }
        if ($request->hasFile('video')) {
            $bg->video = GlobalFunction::saveFileAndGivePath($request->video);
        }

        $bg->save();

        return GlobalFunction::sendSimpleResponse(true, 'Background added successfully');
    }

    public function editGreenScreenBg(Request $request)
    {
        $bg = GreenScreenBg::find($request->id);
        if (!$bg) {
            return GlobalFunction::sendSimpleResponse(false, 'Background not found');
        }

        if ($request->has('title')) $bg->title = $request->title;
        if ($request->has('category')) $bg->category = $request->category;
        if ($request->has('type')) $bg->type = $request->type;
        if ($request->has('sort_order')) $bg->sort_order = $request->sort_order;

        if ($request->hasFile('image')) {
            if ($bg->image) GlobalFunction::deleteFile($bg->image);
            $bg->image = GlobalFunction::saveFileAndGivePath($request->image);
        }
        if ($request->hasFile('video')) {
            if ($bg->video) GlobalFunction::deleteFile($bg->video);
            $bg->video = GlobalFunction::saveFileAndGivePath($request->video);
        }

        $bg->save();

        return GlobalFunction::sendSimpleResponse(true, 'Background updated successfully');
    }

    public function toggleGreenScreenBgStatus(Request $request)
    {
        $bg = GreenScreenBg::find($request->id);
        if (!$bg) {
            return GlobalFunction::sendSimpleResponse(false, 'Background not found');
        }
        $bg->is_active = !$bg->is_active;
        $bg->save();
        return GlobalFunction::sendSimpleResponse(true, 'Status updated successfully');
    }

    public function deleteGreenScreenBg(Request $request)
    {
        $bg = GreenScreenBg::find($request->id);
        if (!$bg) {
            return GlobalFunction::sendSimpleResponse(false, 'Background not found');
        }
        if ($bg->image) GlobalFunction::deleteFile($bg->image);
        if ($bg->video) GlobalFunction::deleteFile($bg->video);
        $bg->delete();
        return GlobalFunction::sendSimpleResponse(true, 'Background deleted successfully');
    }
}
