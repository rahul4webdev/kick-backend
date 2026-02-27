<?php

namespace App\Http\Controllers;

use App\Models\Constants;
use App\Models\GlobalFunction;
use App\Models\Posts;
use App\Models\Template;
use App\Models\TemplateClip;
use App\Models\Users;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TemplateController extends Controller
{
    // ─── API Endpoints ──────────────────────────────────────────

    public function fetchTemplates(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => 'This user is frozen!'];
        }

        $query = Template::where('is_active', true)
            ->with('clips', 'music')
            ->orderBy('sort_order', 'ASC')
            ->orderByDesc('use_count');

        // Filter by source: 'official', 'user', or all
        if ($request->source == 'user') {
            $query->where('is_user_created', true);
        } elseif ($request->source == 'official') {
            $query->where('is_user_created', false);
        }

        if ($request->has('category') && !empty($request->category)) {
            $query->where('category', $request->category);
        }

        $limit = $request->limit ?? 20;
        if ($request->has('last_item_id')) {
            $query->where('id', '<', $request->last_item_id);
        }

        $templates = $query->limit($limit)->get();

        // Attach creator info for user-created templates
        foreach ($templates as $template) {
            if ($template->creator_id) {
                $template->creator = Users::select(explode(',', Constants::userPublicFields))
                    ->find($template->creator_id);
            }
            $template->is_liked = DB::table('tbl_template_likes')
                ->where('template_id', $template->id)
                ->where('user_id', $user->id)
                ->exists();
        }

        // Get distinct categories for filter
        $categories = Template::where('is_active', true)
            ->whereNotNull('category')
            ->distinct()
            ->pluck('category');

        return response()->json([
            'status' => true,
            'message' => 'Templates fetched successfully',
            'data' => $templates,
            'categories' => $categories,
        ]);
    }

    public function fetchTemplateById(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => 'This user is frozen!'];
        }

        $template = Template::where('id', $request->template_id)
            ->where('is_active', true)
            ->with('clips', 'music')
            ->first();

        if (!$template) {
            return GlobalFunction::sendSimpleResponse(false, 'Template not found');
        }

        return GlobalFunction::sendDataResponse(true, 'Template fetched successfully', $template);
    }

    public function incrementTemplateUse(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => 'This user is frozen!'];
        }

        $template = Template::find($request->template_id);
        if ($template) {
            $template->increment('use_count');

            // Track usage
            DB::table('tbl_template_uses')->insert([
                'template_id' => $template->id,
                'user_id' => $user->id,
                'post_id' => $request->post_id,
                'created_at' => now(),
            ]);
        }

        return GlobalFunction::sendSimpleResponse(true, 'Template use count incremented');
    }

    /**
     * Create a user template from an existing reel post.
     */
    public function createUserTemplate(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if (!$user) {
            return GlobalFunction::sendSimpleResponse(false, 'User not found!');
        }

        $rules = [
            'name' => 'required|string|max:200',
            'clip_count' => 'required|integer|min:1|max:10',
            'duration_sec' => 'required|integer|min:1',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return GlobalFunction::sendSimpleResponse(false, $validator->errors()->first());
        }

        // Verify source post belongs to user if provided
        if ($request->source_post_id) {
            $post = Posts::find($request->source_post_id);
            if (!$post || $post->user_id != $user->id) {
                return GlobalFunction::sendSimpleResponse(false, 'Post not found or does not belong to you');
            }
        }

        $template = new Template();
        $template->creator_id = $user->id;
        $template->is_user_created = true;
        $template->source_post_id = $request->source_post_id;
        $template->name = $request->name;
        $template->description = $request->description;
        $template->clip_count = $request->clip_count;
        $template->duration_sec = $request->duration_sec;
        $template->category = $request->category ?? 'User Created';
        $template->is_active = true;

        if ($request->thumbnail) {
            $template->thumbnail = $request->thumbnail;
        }
        if ($request->preview_video) {
            $template->preview_video = $request->preview_video;
        }
        if ($request->has('transition_data')) {
            $template->transition_data = json_decode($request->transition_data, true);
        }

        $template->save();

        // Create clip slots
        $clipDuration = (int)(($request->duration_sec * 1000) / $request->clip_count);
        $clips = json_decode($request->clips_json ?? '[]', true);

        for ($i = 0; $i < $request->clip_count; $i++) {
            $clipData = $clips[$i] ?? [];
            TemplateClip::create([
                'template_id' => $template->id,
                'clip_index' => $i,
                'duration_ms' => $clipData['duration_ms'] ?? $clipDuration,
                'label' => $clipData['label'] ?? 'Clip ' . ($i + 1),
                'transition_to_next' => $clipData['transition_to_next'] ?? 'cut',
                'transition_duration_ms' => $clipData['transition_duration_ms'] ?? 300,
            ]);
        }

        $template->load('clips');

        return GlobalFunction::sendDataResponse(true, 'Template created', $template);
    }

    /**
     * Fetch trending templates (sorted by trending_score).
     */
    public function fetchTrendingTemplates(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if (!$user) {
            return GlobalFunction::sendSimpleResponse(false, 'User not found!');
        }

        $query = Template::where('is_active', true)
            ->with('clips', 'music')
            ->orderByDesc('trending_score')
            ->orderByDesc('use_count');

        $limit = $request->limit ?? 20;
        if ($request->last_item_id) {
            $query->where('id', '<', $request->last_item_id);
        }

        $templates = $query->limit($limit)->get();

        foreach ($templates as $template) {
            if ($template->creator_id) {
                $template->creator = Users::select(explode(',', Constants::userPublicFields))
                    ->find($template->creator_id);
            }
            $template->is_liked = DB::table('tbl_template_likes')
                ->where('template_id', $template->id)
                ->where('user_id', $user->id)
                ->exists();
        }

        return GlobalFunction::sendDataResponse(true, 'Trending templates fetched', $templates);
    }

    /**
     * Like / unlike a template.
     */
    public function likeTemplate(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if (!$user) {
            return GlobalFunction::sendSimpleResponse(false, 'User not found!');
        }

        $template = Template::find($request->template_id);
        if (!$template) {
            return GlobalFunction::sendSimpleResponse(false, 'Template not found');
        }

        $exists = DB::table('tbl_template_likes')
            ->where('template_id', $template->id)
            ->where('user_id', $user->id)
            ->exists();

        if ($exists) {
            DB::table('tbl_template_likes')
                ->where('template_id', $template->id)
                ->where('user_id', $user->id)
                ->delete();
            $template->decrement('like_count');
            return GlobalFunction::sendSimpleResponse(true, 'Template unliked');
        }

        DB::table('tbl_template_likes')->insert([
            'template_id' => $template->id,
            'user_id' => $user->id,
            'created_at' => now(),
        ]);
        $template->increment('like_count');

        return GlobalFunction::sendSimpleResponse(true, 'Template liked');
    }

    /**
     * Fetch usages of a template.
     */
    public function fetchTemplateUsages(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if (!$user) {
            return GlobalFunction::sendSimpleResponse(false, 'User not found!');
        }

        $usages = DB::table('tbl_template_uses')
            ->where('template_id', $request->template_id)
            ->orderByDesc('created_at')
            ->limit($request->limit ?? 20)
            ->get();

        foreach ($usages as $usage) {
            $usage->user = Users::select(explode(',', Constants::userPublicFields))
                ->find($usage->user_id);
        }

        return GlobalFunction::sendDataResponse(true, 'Template usages fetched', $usages);
    }

    // ─── Admin Endpoints ────────────────────────────────────────

    public function templates()
    {
        return view('templates');
    }

    public function listTemplates(Request $request)
    {
        $query = Template::with('clips');
        $totalData = $query->count();

        $limit = $request->input('length');
        $start = $request->input('start');
        $searchValue = $request->input('search.value');

        if (!empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('name', 'LIKE', "%{$searchValue}%")
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
            $thumbUrl = $item->thumbnail ? GlobalFunction::generateFileUrl($item->thumbnail) : url('assets/img/placeholder.png');
            $thumb = "<img class='rounded' width='50' height='50' src='{$thumbUrl}' alt=''>";

            $activeStatus = $item->is_active
                ? "<span class='badge bg-success'>Active</span>"
                : "<span class='badge bg-warning'>Inactive</span>";

            $clipLabels = $item->clips->map(fn($c) => $c->label ?? "Clip " . ($c->clip_index + 1))->implode(', ');

            $edit = "<a href='#'
                        rel='{$item->id}'
                        data-name='" . htmlspecialchars($item->name, ENT_QUOTES) . "'
                        data-description='" . htmlspecialchars($item->description ?? '', ENT_QUOTES) . "'
                        data-clip-count='{$item->clip_count}'
                        data-duration-sec='{$item->duration_sec}'
                        data-category='" . htmlspecialchars($item->category ?? '', ENT_QUOTES) . "'
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
                htmlspecialchars($item->name),
                htmlspecialchars($item->category ?? '-'),
                $item->clip_count,
                $item->duration_sec . 's',
                $item->use_count,
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

    public function addTemplate(Request $request)
    {
        $rules = [
            'name' => 'required|string|max:200',
            'clip_count' => 'required|integer|min:1|max:20',
            'duration_sec' => 'required|integer|min:1',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $template = new Template();
        $template->name = $request->name;
        $template->description = $request->description;
        $template->clip_count = $request->clip_count;
        $template->duration_sec = $request->duration_sec;
        $template->category = $request->category;
        $template->music_id = $request->music_id;
        $template->sort_order = $request->sort_order ?? 0;
        $template->is_active = true;

        if ($request->hasFile('thumbnail')) {
            $template->thumbnail = GlobalFunction::saveFileAndGivePath($request->thumbnail);
        }
        if ($request->hasFile('preview_video')) {
            $template->preview_video = GlobalFunction::saveFileAndGivePath($request->preview_video);
        }

        // Parse transition data
        if ($request->has('transition_data')) {
            $template->transition_data = json_decode($request->transition_data, true);
        }

        $template->save();

        // Auto-create clip slots with equal duration
        $clipDuration = (int)(($request->duration_sec * 1000) / $request->clip_count);
        $clips = json_decode($request->clips_json ?? '[]', true);

        for ($i = 0; $i < $request->clip_count; $i++) {
            $clipData = $clips[$i] ?? [];
            TemplateClip::create([
                'template_id' => $template->id,
                'clip_index' => $i,
                'duration_ms' => $clipData['duration_ms'] ?? $clipDuration,
                'label' => $clipData['label'] ?? 'Clip ' . ($i + 1),
                'transition_to_next' => $clipData['transition_to_next'] ?? 'cut',
                'transition_duration_ms' => $clipData['transition_duration_ms'] ?? 300,
            ]);
        }

        return GlobalFunction::sendSimpleResponse(true, 'Template created successfully');
    }

    public function editTemplate(Request $request)
    {
        $template = Template::find($request->id);
        if (!$template) {
            return GlobalFunction::sendSimpleResponse(false, 'Template not found');
        }

        if ($request->has('name')) $template->name = $request->name;
        if ($request->has('description')) $template->description = $request->description;
        if ($request->has('category')) $template->category = $request->category;
        if ($request->has('duration_sec')) $template->duration_sec = $request->duration_sec;
        if ($request->has('music_id')) $template->music_id = $request->music_id;
        if ($request->has('sort_order')) $template->sort_order = $request->sort_order;

        if ($request->has('transition_data')) {
            $template->transition_data = json_decode($request->transition_data, true);
        }

        if ($request->hasFile('thumbnail')) {
            if ($template->thumbnail) GlobalFunction::deleteFile($template->thumbnail);
            $template->thumbnail = GlobalFunction::saveFileAndGivePath($request->thumbnail);
        }
        if ($request->hasFile('preview_video')) {
            if ($template->preview_video) GlobalFunction::deleteFile($template->preview_video);
            $template->preview_video = GlobalFunction::saveFileAndGivePath($request->preview_video);
        }

        // If clip count changed, recreate clips
        if ($request->has('clip_count') && $request->clip_count != $template->clip_count) {
            $template->clip_count = $request->clip_count;
            TemplateClip::where('template_id', $template->id)->delete();

            $clipDuration = (int)(($template->duration_sec * 1000) / $template->clip_count);
            $clips = json_decode($request->clips_json ?? '[]', true);

            for ($i = 0; $i < $template->clip_count; $i++) {
                $clipData = $clips[$i] ?? [];
                TemplateClip::create([
                    'template_id' => $template->id,
                    'clip_index' => $i,
                    'duration_ms' => $clipData['duration_ms'] ?? $clipDuration,
                    'label' => $clipData['label'] ?? 'Clip ' . ($i + 1),
                    'transition_to_next' => $clipData['transition_to_next'] ?? 'cut',
                    'transition_duration_ms' => $clipData['transition_duration_ms'] ?? 300,
                ]);
            }
        }

        $template->save();

        return GlobalFunction::sendSimpleResponse(true, 'Template updated successfully');
    }

    public function toggleTemplateStatus(Request $request)
    {
        $template = Template::find($request->id);
        if (!$template) {
            return GlobalFunction::sendSimpleResponse(false, 'Template not found');
        }
        $template->is_active = !$template->is_active;
        $template->save();

        return GlobalFunction::sendSimpleResponse(true, $template->is_active ? 'Template activated' : 'Template deactivated');
    }

    public function deleteTemplate(Request $request)
    {
        $template = Template::find($request->id);
        if (!$template) {
            return GlobalFunction::sendSimpleResponse(false, 'Template not found');
        }

        if ($template->thumbnail) GlobalFunction::deleteFile($template->thumbnail);
        if ($template->preview_video) GlobalFunction::deleteFile($template->preview_video);

        // Clips cascade delete via FK
        $template->delete();

        return GlobalFunction::sendSimpleResponse(true, 'Template deleted successfully');
    }
}
