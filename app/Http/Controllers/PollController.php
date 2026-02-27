<?php

namespace App\Http\Controllers;

use App\Models\Constants;
use App\Models\GlobalFunction;
use App\Models\Poll;
use App\Models\PollOption;
use App\Models\PollVote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PollController extends Controller
{
    /**
     * Create a poll post (text post with attached poll).
     */
    public function createPollPost(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        $canPost = GlobalFunction::checkIfUserCanPost($user);
        if (!$canPost['status']) {
            return response()->json($canPost);
        }

        $rules = [
            'question' => 'required|string|max:500',
            'options' => 'required|array|min:2|max:6',
            'options.*.text' => 'required|string|max:200',
            'poll_type' => 'nullable|integer|in:0,1',
            'allow_multiple' => 'nullable|boolean',
            'ends_at' => 'nullable|date|after:now',
            'visibility' => 'nullable|integer|in:0,1,2,3',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        // Create the text post with the poll question as description
        $request->merge([
            'description' => $request->question,
            'can_comment' => $request->has('can_comment') ? $request->can_comment : 1,
        ]);

        $post = GlobalFunction::generatePost($request, Constants::postTypeText, $user, null);

        // Create the poll
        $poll = new Poll();
        $poll->post_id = $post->id;
        $poll->question = $request->question;
        $poll->poll_type = $request->input('poll_type', 0);
        $poll->allow_multiple = $request->input('allow_multiple', false);
        $poll->ends_at = $request->input('ends_at');
        $poll->save();

        // Create poll options
        $options = $request->input('options', []);
        foreach ($options as $index => $optionData) {
            $option = new PollOption();
            $option->poll_id = $poll->id;
            $option->option_text = $optionData['text'];
            $option->option_image = $optionData['image'] ?? null;
            $option->sort_order = $index;
            $option->save();
        }

        // Re-fetch with poll data
        $post = GlobalFunction::preparePostFullData($post->id);
        $post->poll = Poll::with('options')->find($poll->id);

        return GlobalFunction::sendDataResponse(true, 'Poll post created successfully', $post);
    }

    /**
     * Vote on a poll.
     */
    public function voteOnPoll(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => 'this user is freezed!'];
        }

        $rules = [
            'poll_id' => 'required|integer',
            'option_id' => 'required|integer',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $poll = Poll::find($request->poll_id);
        if (!$poll) {
            return GlobalFunction::sendSimpleResponse(false, 'Poll not found');
        }

        if ($poll->is_closed) {
            return GlobalFunction::sendSimpleResponse(false, 'This poll is closed');
        }

        if ($poll->ends_at && now()->gt($poll->ends_at)) {
            $poll->is_closed = true;
            $poll->save();
            return GlobalFunction::sendSimpleResponse(false, 'This poll has ended');
        }

        $option = PollOption::where('id', $request->option_id)
            ->where('poll_id', $request->poll_id)
            ->first();
        if (!$option) {
            return GlobalFunction::sendSimpleResponse(false, 'Invalid option');
        }

        // Check if already voted
        $existingVote = PollVote::where('poll_id', $request->poll_id)
            ->where('user_id', $user->id)
            ->first();

        if ($existingVote) {
            if (!$poll->allow_multiple) {
                // Change vote: decrement old option, remove old vote
                $oldOption = PollOption::find($existingVote->option_id);
                if ($oldOption) {
                    $oldOption->vote_count = max(0, $oldOption->vote_count - 1);
                    $oldOption->save();
                }
                $existingVote->option_id = $request->option_id;
                $existingVote->save();
            } else {
                return GlobalFunction::sendSimpleResponse(false, 'You have already voted');
            }
        } else {
            $vote = new PollVote();
            $vote->poll_id = $request->poll_id;
            $vote->option_id = $request->option_id;
            $vote->user_id = $user->id;
            $vote->save();

            $poll->total_votes = $poll->total_votes + 1;
            $poll->save();
        }

        // Update option vote count
        $option->vote_count = $option->vote_count + 1;
        $option->save();

        // Return updated poll data
        $poll->refresh();
        $poll->load('options');
        $poll->user_vote_option_id = $request->option_id;

        return GlobalFunction::sendDataResponse(true, 'Vote recorded', $poll);
    }

    /**
     * Fetch poll results for a post.
     */
    public function fetchPollResults(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => 'this user is freezed!'];
        }

        $rules = ['post_id' => 'required|integer'];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $poll = Poll::with('options')->where('post_id', $request->post_id)->first();
        if (!$poll) {
            return GlobalFunction::sendSimpleResponse(false, 'Poll not found');
        }

        // Auto-close expired polls
        if ($poll->ends_at && now()->gt($poll->ends_at) && !$poll->is_closed) {
            $poll->is_closed = true;
            $poll->save();
        }

        // Check user's vote
        $userVote = PollVote::where('poll_id', $poll->id)
            ->where('user_id', $user->id)
            ->first();
        $poll->user_vote_option_id = $userVote?->option_id;

        return GlobalFunction::sendDataResponse(true, 'Poll results fetched', $poll);
    }

    /**
     * Close a poll (only post owner).
     */
    public function closePoll(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => 'this user is freezed!'];
        }

        $rules = ['poll_id' => 'required|integer'];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $poll = Poll::find($request->poll_id);
        if (!$poll) {
            return GlobalFunction::sendSimpleResponse(false, 'Poll not found');
        }

        // Only post owner can close
        $post = $poll->post;
        if (!$post || $post->user_id != $user->id) {
            return GlobalFunction::sendSimpleResponse(false, 'Only the post owner can close this poll');
        }

        $poll->is_closed = true;
        $poll->save();

        return GlobalFunction::sendSimpleResponse(true, 'Poll closed successfully');
    }
}
