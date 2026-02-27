<?php

namespace App\Http\Controllers;

use App\Models\Constants;
use App\Models\GlobalFunction;
use App\Models\Story;
use App\Models\StoryHighlight;
use App\Models\StoryHighlightItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class StoryHighlightController extends Controller
{
    /**
     * Create a new story highlight.
     */
    public function createHighlight(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => 'This user is frozen!'];
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $highlight = new StoryHighlight();
        $highlight->user_id = $user->id;
        $highlight->name = $request->name;

        if ($request->has('cover_image') && $request->cover_image) {
            $highlight->cover_image = $request->cover_image;
        }

        // Set sort_order to be last
        $maxSort = StoryHighlight::where('user_id', $user->id)->max('sort_order') ?? 0;
        $highlight->sort_order = $maxSort + 1;

        $highlight->save();

        // If story_ids provided, add them as items
        if ($request->has('story_ids') && $request->story_ids) {
            $storyIds = is_array($request->story_ids) ? $request->story_ids : explode(',', $request->story_ids);
            $this->addStoriesToHighlight($highlight, $storyIds, $user->id);
        }

        $highlight->load('items');

        return GlobalFunction::sendDataResponse(true, 'Highlight created successfully', $highlight);
    }

    /**
     * Fetch highlights for a user.
     */
    public function fetchHighlights(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);

        $userId = $request->has('user_id') ? $request->user_id : $user->id;

        $highlights = StoryHighlight::where('user_id', $userId)
            ->orderBy('sort_order')
            ->with('items')
            ->get();

        return GlobalFunction::sendDataResponse(true, 'Highlights fetched successfully', $highlights);
    }

    /**
     * Fetch a single highlight with its items.
     */
    public function fetchHighlightById(Request $request)
    {
        $token = $request->header('authtoken');
        GlobalFunction::getUserFromAuthToken($token);

        $validator = Validator::make($request->all(), [
            'highlight_id' => 'required|exists:tbl_story_highlights,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $highlight = StoryHighlight::where('id', $request->highlight_id)
            ->with('items')
            ->first();

        return GlobalFunction::sendDataResponse(true, 'Highlight fetched successfully', $highlight);
    }

    /**
     * Update highlight name or cover.
     */
    public function updateHighlight(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);

        $validator = Validator::make($request->all(), [
            'highlight_id' => 'required|exists:tbl_story_highlights,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $highlight = StoryHighlight::find($request->highlight_id);

        if ($highlight->user_id != $user->id) {
            return GlobalFunction::sendSimpleResponse(false, 'Not authorized');
        }

        if ($request->has('name') && $request->name) {
            $highlight->name = $request->name;
        }

        if ($request->has('cover_image')) {
            $highlight->cover_image = $request->cover_image;
        }

        $highlight->save();
        $highlight->load('items');

        return GlobalFunction::sendDataResponse(true, 'Highlight updated successfully', $highlight);
    }

    /**
     * Delete a highlight.
     */
    public function deleteHighlight(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);

        $validator = Validator::make($request->all(), [
            'highlight_id' => 'required|exists:tbl_story_highlights,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $highlight = StoryHighlight::find($request->highlight_id);

        if ($highlight->user_id != $user->id) {
            return GlobalFunction::sendSimpleResponse(false, 'Not authorized');
        }

        // Items are cascade-deleted via FK
        $highlight->delete();

        return GlobalFunction::sendSimpleResponse(true, 'Highlight deleted successfully');
    }

    /**
     * Add stories to an existing highlight.
     * Stories are copied (content/thumbnail/type/duration) so they persist after expiry.
     */
    public function addStoryToHighlight(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);

        $validator = Validator::make($request->all(), [
            'highlight_id' => 'required|exists:tbl_story_highlights,id',
            'story_id' => 'required|exists:stories,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $highlight = StoryHighlight::find($request->highlight_id);

        if ($highlight->user_id != $user->id) {
            return GlobalFunction::sendSimpleResponse(false, 'Not authorized');
        }

        $story = Story::find($request->story_id);

        if ($story->user_id != $user->id) {
            return GlobalFunction::sendSimpleResponse(false, 'You can only add your own stories');
        }

        // Check if story is already in this highlight
        $exists = StoryHighlightItem::where('highlight_id', $highlight->id)
            ->where('original_story_id', $story->id)
            ->exists();

        if ($exists) {
            return GlobalFunction::sendSimpleResponse(false, 'Story already in this highlight');
        }

        $maxSort = StoryHighlightItem::where('highlight_id', $highlight->id)->max('sort_order') ?? 0;

        $item = new StoryHighlightItem();
        $item->highlight_id = $highlight->id;
        $item->original_story_id = $story->id;
        $item->type = $story->type;
        $item->content = $story->content;
        $item->thumbnail = $story->thumbnail;
        $item->duration = $story->duration;
        $item->sort_order = $maxSort + 1;
        $item->save();

        // Update cover image if first item
        if (!$highlight->cover_image) {
            $highlight->cover_image = $story->thumbnail ?? $story->content;
        }
        $highlight->item_count = StoryHighlightItem::where('highlight_id', $highlight->id)->count();
        $highlight->save();

        $highlight->load('items');

        return GlobalFunction::sendDataResponse(true, 'Story added to highlight', $highlight);
    }

    /**
     * Remove an item from a highlight.
     */
    public function removeHighlightItem(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);

        $validator = Validator::make($request->all(), [
            'item_id' => 'required|exists:tbl_story_highlight_items,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $item = StoryHighlightItem::find($request->item_id);
        $highlight = StoryHighlight::find($item->highlight_id);

        if ($highlight->user_id != $user->id) {
            return GlobalFunction::sendSimpleResponse(false, 'Not authorized');
        }

        $item->delete();

        // Update item count
        $highlight->item_count = StoryHighlightItem::where('highlight_id', $highlight->id)->count();
        $highlight->save();

        // Delete highlight if no items left
        if ($highlight->item_count == 0) {
            $highlight->delete();
            return GlobalFunction::sendSimpleResponse(true, 'Highlight deleted (no items remaining)');
        }

        $highlight->load('items');

        return GlobalFunction::sendDataResponse(true, 'Item removed from highlight', $highlight);
    }

    /**
     * Reorder highlights.
     */
    public function reorderHighlights(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);

        $validator = Validator::make($request->all(), [
            'highlight_ids' => 'required', // comma-separated or array of highlight IDs in desired order
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $ids = is_array($request->highlight_ids) ? $request->highlight_ids : explode(',', $request->highlight_ids);

        foreach ($ids as $index => $highlightId) {
            StoryHighlight::where('id', $highlightId)
                ->where('user_id', $user->id)
                ->update(['sort_order' => $index]);
        }

        return GlobalFunction::sendSimpleResponse(true, 'Highlights reordered successfully');
    }

    /**
     * Helper: Add multiple stories to a highlight.
     */
    private function addStoriesToHighlight(StoryHighlight $highlight, array $storyIds, int $userId)
    {
        $maxSort = 0;
        $coverSet = false;

        foreach ($storyIds as $storyId) {
            $story = Story::where('id', $storyId)->where('user_id', $userId)->first();
            if (!$story) continue;

            // Check duplicate
            $exists = StoryHighlightItem::where('highlight_id', $highlight->id)
                ->where('original_story_id', $story->id)
                ->exists();
            if ($exists) continue;

            $item = new StoryHighlightItem();
            $item->highlight_id = $highlight->id;
            $item->original_story_id = $story->id;
            $item->type = $story->type;
            $item->content = $story->content;
            $item->thumbnail = $story->thumbnail;
            $item->duration = $story->duration;
            $item->sort_order = $maxSort++;
            $item->save();

            if (!$coverSet && !$highlight->cover_image) {
                $highlight->cover_image = $story->thumbnail ?? $story->content;
                $coverSet = true;
            }
        }

        $highlight->item_count = StoryHighlightItem::where('highlight_id', $highlight->id)->count();
        $highlight->save();
    }
}
