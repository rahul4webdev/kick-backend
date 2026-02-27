<?php

namespace App\Http\Controllers;

use App\Models\Constants;
use App\Models\GlobalFunction;
use App\Models\Question;
use App\Models\QuestionLike;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class QuestionController extends Controller
{
    /**
     * Ask a question on someone's profile.
     */
    public function askQuestion(Request $request)
    {
        $user = GlobalFunction::getAuthUser();
        if (!$user) return GlobalFunction::sendSimpleResponse(false, 'Unauthorized');

        $request->validate([
            'user_id' => 'required|integer',
            'question' => 'required|string|max:500',
        ]);

        $profileUserId = $request->user_id;
        if ($profileUserId == $user->id) {
            return GlobalFunction::sendSimpleResponse(false, 'Cannot ask yourself a question');
        }

        // Check if blocked
        $blockedIds = GlobalFunction::getUsersBlockedUsersIdsArray($user->id);
        if (in_array($profileUserId, $blockedIds)) {
            return GlobalFunction::sendSimpleResponse(false, 'Cannot ask this user a question');
        }

        $question = Question::create([
            'profile_user_id' => $profileUserId,
            'asked_by_user_id' => $user->id,
            'question' => $request->question,
        ]);

        return GlobalFunction::sendSimpleResponse(true, 'Question submitted', [
            'question' => $this->formatQuestion($question, $user->id),
        ]);
    }

    /**
     * Answer a question (only the profile owner can answer).
     */
    public function answerQuestion(Request $request)
    {
        $user = GlobalFunction::getAuthUser();
        if (!$user) return GlobalFunction::sendSimpleResponse(false, 'Unauthorized');

        $request->validate([
            'question_id' => 'required|integer',
            'answer' => 'required|string|max:2000',
        ]);

        $question = Question::find($request->question_id);
        if (!$question) return GlobalFunction::sendSimpleResponse(false, 'Question not found');
        if ($question->profile_user_id != $user->id) {
            return GlobalFunction::sendSimpleResponse(false, 'Only the profile owner can answer');
        }

        $question->answer = $request->answer;
        $question->answered_at = now();
        $question->save();

        return GlobalFunction::sendSimpleResponse(true, 'Answer saved', [
            'question' => $this->formatQuestion($question, $user->id),
        ]);
    }

    /**
     * Delete a question (profile owner or asker can delete).
     */
    public function deleteQuestion(Request $request)
    {
        $user = GlobalFunction::getAuthUser();
        if (!$user) return GlobalFunction::sendSimpleResponse(false, 'Unauthorized');

        $request->validate(['question_id' => 'required|integer']);

        $question = Question::find($request->question_id);
        if (!$question) return GlobalFunction::sendSimpleResponse(false, 'Question not found');

        if ($question->profile_user_id != $user->id && $question->asked_by_user_id != $user->id) {
            return GlobalFunction::sendSimpleResponse(false, 'Not authorized to delete this question');
        }

        $question->delete();
        return GlobalFunction::sendSimpleResponse(true, 'Question deleted');
    }

    /**
     * Hide/unhide a question (profile owner only).
     */
    public function toggleHideQuestion(Request $request)
    {
        $user = GlobalFunction::getAuthUser();
        if (!$user) return GlobalFunction::sendSimpleResponse(false, 'Unauthorized');

        $request->validate(['question_id' => 'required|integer']);

        $question = Question::find($request->question_id);
        if (!$question) return GlobalFunction::sendSimpleResponse(false, 'Question not found');
        if ($question->profile_user_id != $user->id) {
            return GlobalFunction::sendSimpleResponse(false, 'Only the profile owner can hide questions');
        }

        $question->is_hidden = !$question->is_hidden;
        $question->save();

        return GlobalFunction::sendSimpleResponse(true, $question->is_hidden ? 'Question hidden' : 'Question visible');
    }

    /**
     * Pin/unpin a question (profile owner only).
     */
    public function togglePinQuestion(Request $request)
    {
        $user = GlobalFunction::getAuthUser();
        if (!$user) return GlobalFunction::sendSimpleResponse(false, 'Unauthorized');

        $request->validate(['question_id' => 'required|integer']);

        $question = Question::find($request->question_id);
        if (!$question) return GlobalFunction::sendSimpleResponse(false, 'Question not found');
        if ($question->profile_user_id != $user->id) {
            return GlobalFunction::sendSimpleResponse(false, 'Only the profile owner can pin questions');
        }

        $question->is_pinned = !$question->is_pinned;
        $question->save();

        return GlobalFunction::sendSimpleResponse(true, $question->is_pinned ? 'Question pinned' : 'Question unpinned');
    }

    /**
     * Like/unlike a question.
     */
    public function likeQuestion(Request $request)
    {
        $user = GlobalFunction::getAuthUser();
        if (!$user) return GlobalFunction::sendSimpleResponse(false, 'Unauthorized');

        $request->validate(['question_id' => 'required|integer']);

        $question = Question::find($request->question_id);
        if (!$question) return GlobalFunction::sendSimpleResponse(false, 'Question not found');

        $existing = QuestionLike::where('question_id', $question->id)->where('user_id', $user->id)->first();
        if ($existing) {
            $existing->delete();
            $question->decrement('like_count');
            return GlobalFunction::sendSimpleResponse(true, 'Like removed');
        } else {
            QuestionLike::create(['question_id' => $question->id, 'user_id' => $user->id]);
            $question->increment('like_count');
            return GlobalFunction::sendSimpleResponse(true, 'Liked');
        }
    }

    /**
     * Fetch questions for a profile.
     */
    public function fetchQuestions(Request $request)
    {
        $user = GlobalFunction::getAuthUser();
        if (!$user) return GlobalFunction::sendSimpleResponse(false, 'Unauthorized');

        $request->validate(['user_id' => 'required|integer']);

        $profileUserId = $request->user_id;
        $isOwner = $profileUserId == $user->id;
        $limit = $request->limit ?? 20;
        $lastItemId = $request->last_item_id;

        $query = Question::where('profile_user_id', $profileUserId);

        // Non-owners can't see hidden questions
        if (!$isOwner) {
            $query->where('is_hidden', false);
        }

        if ($lastItemId) {
            $query->where('id', '<', $lastItemId);
        }

        // Pinned first, then newest
        $questions = $query->orderByDesc('is_pinned')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();

        // Get liked question IDs for this user
        $likedIds = QuestionLike::where('user_id', $user->id)
            ->whereIn('question_id', $questions->pluck('id'))
            ->pluck('question_id')
            ->toArray();

        // Get user info for askers
        $userIds = $questions->pluck('asked_by_user_id')->unique()->toArray();
        $userIds[] = $profileUserId;
        $users = \App\Models\User::whereIn('id', $userIds)->get()->keyBy('id');

        $data = $questions->map(function ($q) use ($user, $likedIds, $users) {
            $askedByUser = $users->get($q->asked_by_user_id);
            $profileUser = $users->get($q->profile_user_id);
            return [
                'id' => $q->id,
                'question' => $q->question,
                'answer' => $q->answer,
                'answered_at' => $q->answered_at,
                'is_pinned' => (bool) $q->is_pinned,
                'is_hidden' => (bool) $q->is_hidden,
                'like_count' => $q->like_count,
                'is_liked' => in_array($q->id, $likedIds),
                'created_at' => $q->created_at?->toIso8601String(),
                'asked_by' => $askedByUser ? [
                    'id' => $askedByUser->id,
                    'username' => $askedByUser->username,
                    'fullname' => $askedByUser->fullname,
                    'profile_photo' => $askedByUser->profile_photo,
                    'is_verify' => (bool) $askedByUser->is_verify,
                ] : null,
                'profile_user' => $profileUser ? [
                    'id' => $profileUser->id,
                    'username' => $profileUser->username,
                    'fullname' => $profileUser->fullname,
                    'profile_photo' => $profileUser->profile_photo,
                    'is_verify' => (bool) $profileUser->is_verify,
                ] : null,
            ];
        });

        return GlobalFunction::sendSimpleResponse(true, 'Questions fetched', [
            'questions' => $data,
        ]);
    }

    /**
     * Format a single question for API response.
     */
    private function formatQuestion(Question $question, int $currentUserId): array
    {
        $question->load(['askedBy', 'profileUser']);
        return [
            'id' => $question->id,
            'question' => $question->question,
            'answer' => $question->answer,
            'answered_at' => $question->answered_at,
            'is_pinned' => (bool) $question->is_pinned,
            'is_hidden' => (bool) $question->is_hidden,
            'like_count' => $question->like_count,
            'is_liked' => false,
            'created_at' => $question->created_at?->toIso8601String(),
            'asked_by' => $question->askedBy ? [
                'id' => $question->askedBy->id,
                'username' => $question->askedBy->username,
                'fullname' => $question->askedBy->fullname,
                'profile_photo' => $question->askedBy->profile_photo,
                'is_verify' => (bool) $question->askedBy->is_verify,
            ] : null,
            'profile_user' => $question->profileUser ? [
                'id' => $question->profileUser->id,
                'username' => $question->profileUser->username,
                'fullname' => $question->profileUser->fullname,
                'profile_photo' => $question->profileUser->profile_photo,
                'is_verify' => (bool) $question->profileUser->is_verify,
            ] : null,
        ];
    }
}
