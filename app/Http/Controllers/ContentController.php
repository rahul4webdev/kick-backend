<?php

namespace App\Http\Controllers;

use App\Models\Constants;
use App\Models\ContentGenre;
use App\Models\ContentLanguage;
use App\Models\GlobalFunction;
use Illuminate\Http\Request;

class ContentController extends Controller
{
    // ─── Content Genres ─────────────────────────────────────────

    public function contentGenres()
    {
        return view('content_genres');
    }

    public function listContentGenres(Request $request)
    {
        $query = ContentGenre::where('is_active', true);

        // Filter by content_type if provided
        if ($request->has('content_type') && $request->content_type !== null && $request->content_type !== '') {
            $query->where('content_type', $request->content_type);
        }

        $totalData = $query->count();

        $limit = $request->input('length');
        $start = $request->input('start');
        $searchValue = $request->input('search.value');

        if (!empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('name', 'LIKE', "%{$searchValue}%");
            });
        }
        $totalFiltered = $query->count();

        $result = $query->offset($start)
                        ->limit($limit)
                        ->orderBy('sort_order', 'ASC')
                        ->orderBy('id', 'DESC')
                        ->get();

        $data = $result->map(function ($item) {
            $contentTypeLabel = Constants::contentTypeLabels[$item->content_type] ?? 'Unknown';
            $typeBadge = "<span class='badge bg-primary-subtle text-primary'>{$contentTypeLabel}</span>";

            $edit = "<a href='#'
                        rel='{$item->id}'
                        data-name='{$item->name}'
                        data-content-type='{$item->content_type}'
                        data-sort-order='{$item->sort_order}'
                        class='action-btn edit d-flex align-items-center justify-content-center btn border rounded-2 text-success ms-1'>
                        <i class='uil-pen'></i>
                        </a>";

            $delete = "<a href='#'
                          rel='{$item->id}'
                          class='action-btn delete d-flex align-items-center justify-content-center btn border rounded-2 text-danger ms-1'>
                            <i class='uil-trash-alt'></i>
                        </a>";
            $action = "<span class='d-flex justify-content-end align-items-center'>{$edit}{$delete}</span>";

            return [
                $item->name,
                $typeBadge,
                $item->sort_order ?? 0,
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

    public function addContentGenre(Request $request)
    {
        $existing = ContentGenre::where('name', $request->name)
                                ->where('content_type', $request->content_type)
                                ->where('is_active', true)
                                ->first();
        if ($existing) {
            return GlobalFunction::sendSimpleResponse(false, 'Genre already exists for this content type!');
        }

        $genre = new ContentGenre();
        $genre->name = $request->name;
        $genre->content_type = $request->content_type;
        $genre->sort_order = $request->sort_order ?? 0;
        $genre->is_active = true;
        $genre->save();

        return GlobalFunction::sendSimpleResponse(true, 'Genre added successfully');
    }

    public function editContentGenre(Request $request)
    {
        $genre = ContentGenre::find($request->id);
        if (!$genre) {
            return GlobalFunction::sendSimpleResponse(false, 'Genre not found');
        }

        $genre->name = $request->name;
        $genre->content_type = $request->content_type;
        $genre->sort_order = $request->sort_order ?? 0;
        $genre->save();

        return GlobalFunction::sendSimpleResponse(true, 'Genre updated successfully');
    }

    public function deleteContentGenre(Request $request)
    {
        $genre = ContentGenre::find($request->id);
        if (!$genre) {
            return GlobalFunction::sendSimpleResponse(false, 'Genre not found');
        }

        $genre->is_active = false;
        $genre->save();

        return GlobalFunction::sendSimpleResponse(true, 'Genre deleted successfully');
    }

    // ─── Content Languages ──────────────────────────────────────

    public function contentLanguages()
    {
        return view('content_languages');
    }

    public function listContentLanguages(Request $request)
    {
        $query = ContentLanguage::where('is_active', true);
        $totalData = $query->count();

        $limit = $request->input('length');
        $start = $request->input('start');
        $searchValue = $request->input('search.value');

        if (!empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('name', 'LIKE', "%{$searchValue}%")
                  ->orWhere('code', 'LIKE', "%{$searchValue}%");
            });
        }
        $totalFiltered = $query->count();

        $result = $query->offset($start)
                        ->limit($limit)
                        ->orderBy('sort_order', 'ASC')
                        ->orderBy('id', 'DESC')
                        ->get();

        $data = $result->map(function ($item) {
            $edit = "<a href='#'
                        rel='{$item->id}'
                        data-name='{$item->name}'
                        data-code='{$item->code}'
                        data-sort-order='{$item->sort_order}'
                        class='action-btn edit d-flex align-items-center justify-content-center btn border rounded-2 text-success ms-1'>
                        <i class='uil-pen'></i>
                        </a>";

            $delete = "<a href='#'
                          rel='{$item->id}'
                          class='action-btn delete d-flex align-items-center justify-content-center btn border rounded-2 text-danger ms-1'>
                            <i class='uil-trash-alt'></i>
                        </a>";
            $action = "<span class='d-flex justify-content-end align-items-center'>{$edit}{$delete}</span>";

            return [
                $item->name,
                $item->code ?? '-',
                $item->sort_order ?? 0,
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

    public function addContentLanguage(Request $request)
    {
        $existing = ContentLanguage::where('name', $request->name)
                                   ->where('is_active', true)
                                   ->first();
        if ($existing) {
            return GlobalFunction::sendSimpleResponse(false, 'Language already exists!');
        }

        $lang = new ContentLanguage();
        $lang->name = $request->name;
        $lang->code = $request->code;
        $lang->sort_order = $request->sort_order ?? 0;
        $lang->is_active = true;
        $lang->save();

        return GlobalFunction::sendSimpleResponse(true, 'Language added successfully');
    }

    public function editContentLanguage(Request $request)
    {
        $lang = ContentLanguage::find($request->id);
        if (!$lang) {
            return GlobalFunction::sendSimpleResponse(false, 'Language not found');
        }

        $lang->name = $request->name;
        $lang->code = $request->code;
        $lang->sort_order = $request->sort_order ?? 0;
        $lang->save();

        return GlobalFunction::sendSimpleResponse(true, 'Language updated successfully');
    }

    public function deleteContentLanguage(Request $request)
    {
        $lang = ContentLanguage::find($request->id);
        if (!$lang) {
            return GlobalFunction::sendSimpleResponse(false, 'Language not found');
        }

        $lang->is_active = false;
        $lang->save();

        return GlobalFunction::sendSimpleResponse(true, 'Language deleted successfully');
    }
}
