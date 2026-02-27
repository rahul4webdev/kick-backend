<?php

namespace App\Http\Controllers;

use App\Models\Constants;
use App\Models\GlobalFunction;
use App\Models\Story;
use App\Models\StoryChain;
use App\Models\StoryChainParticipant;
use App\Models\StoryInteraction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class StoryInteractionController extends Controller
{
    /**
     * Vote on a poll sticker in a story.
     * Params: story_id, option_index (integer)
     */
    public function voteOnPoll(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);

        $validator = Validator::make($request->all(), [
            'story_id' => 'required|exists:stories,id',
            'option_index' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $story = Story::find($request->story_id);
        $stickerData = json_decode($story->sticker_data, true);

        if (!$stickerData || ($stickerData['type'] ?? '') !== 'poll') {
            return response()->json(['status' => false, 'message' => 'This story does not have a poll sticker']);
        }

        $optionCount = count($stickerData['options'] ?? []);
        if ($request->option_index >= $optionCount) {
            return response()->json(['status' => false, 'message' => 'Invalid option index']);
        }

        // Upsert: one vote per user per story
        $interaction = StoryInteraction::updateOrCreate(
            [
                'story_id' => $request->story_id,
                'user_id' => $user->id,
                'interaction_type' => 'poll_vote',
            ],
            [
                'data' => ['option_index' => (int) $request->option_index],
            ]
        );

        // Return updated results
        return $this->_getPollResults($story, $user->id);
    }

    /**
     * Fetch poll results for a story.
     * Params: story_id
     */
    public function fetchPollResults(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);

        $validator = Validator::make($request->all(), [
            'story_id' => 'required|exists:stories,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $story = Story::find($request->story_id);
        return $this->_getPollResults($story, $user->id);
    }

    /**
     * Submit a response to a question sticker.
     * Params: story_id, response (string)
     */
    public function submitQuestionResponse(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);

        $validator = Validator::make($request->all(), [
            'story_id' => 'required|exists:stories,id',
            'response' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $story = Story::find($request->story_id);
        $stickerData = json_decode($story->sticker_data, true);

        if (!$stickerData || ($stickerData['type'] ?? '') !== 'question') {
            return response()->json(['status' => false, 'message' => 'This story does not have a question sticker']);
        }

        // One response per user per story
        $interaction = StoryInteraction::updateOrCreate(
            [
                'story_id' => $request->story_id,
                'user_id' => $user->id,
                'interaction_type' => 'question_response',
            ],
            [
                'data' => ['response' => $request->response],
            ]
        );

        return GlobalFunction::sendSimpleResponse(true, 'Response submitted successfully');
    }

    /**
     * Fetch all question responses for a story (creator only).
     * Params: story_id
     */
    public function fetchQuestionResponses(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);

        $validator = Validator::make($request->all(), [
            'story_id' => 'required|exists:stories,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $story = Story::find($request->story_id);

        // Only the story creator can view all responses
        if ($story->user_id !== $user->id) {
            return response()->json(['status' => false, 'message' => 'Only the story creator can view responses']);
        }

        $responses = StoryInteraction::where('story_id', $request->story_id)
            ->where('interaction_type', 'question_response')
            ->with(['user:' . Constants::userPublicFields])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($interaction) {
                return [
                    'id' => $interaction->id,
                    'user' => $interaction->user,
                    'response' => $interaction->data['response'] ?? '',
                    'created_at' => $interaction->created_at,
                ];
            });

        return GlobalFunction::sendDataResponse(true, 'Responses fetched', $responses);
    }

    /**
     * Answer a quiz sticker in a story.
     * Params: story_id, option_index (integer)
     */
    public function answerQuiz(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);

        $validator = Validator::make($request->all(), [
            'story_id' => 'required|exists:stories,id',
            'option_index' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $story = Story::find($request->story_id);
        $stickerData = json_decode($story->sticker_data, true);

        if (!$stickerData || ($stickerData['type'] ?? '') !== 'quiz') {
            return response()->json(['status' => false, 'message' => 'This story does not have a quiz sticker']);
        }

        $optionCount = count($stickerData['options'] ?? []);
        if ($request->option_index >= $optionCount) {
            return response()->json(['status' => false, 'message' => 'Invalid option index']);
        }

        $correctIndex = (int) ($stickerData['correct_index'] ?? 0);
        $isCorrect = (int) $request->option_index === $correctIndex;

        // One answer per user per story
        StoryInteraction::updateOrCreate(
            [
                'story_id' => $request->story_id,
                'user_id' => $user->id,
                'interaction_type' => 'quiz_answer',
            ],
            [
                'data' => ['option_index' => (int) $request->option_index, 'is_correct' => $isCorrect],
            ]
        );

        return $this->_getQuizResults($story, $user->id);
    }

    /**
     * Fetch quiz results for a story.
     * Params: story_id
     */
    public function fetchQuizResults(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);

        $validator = Validator::make($request->all(), [
            'story_id' => 'required|exists:stories,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $story = Story::find($request->story_id);
        return $this->_getQuizResults($story, $user->id);
    }

    /**
     * Submit emoji slider response.
     * Params: story_id, value (float 0.0-1.0)
     */
    public function submitSlider(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);

        $validator = Validator::make($request->all(), [
            'story_id' => 'required|exists:stories,id',
            'value' => 'required|numeric|min:0|max:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $story = Story::find($request->story_id);
        $stickerData = json_decode($story->sticker_data, true);

        if (!$stickerData || ($stickerData['type'] ?? '') !== 'slider') {
            return response()->json(['status' => false, 'message' => 'This story does not have a slider sticker']);
        }

        StoryInteraction::updateOrCreate(
            [
                'story_id' => $request->story_id,
                'user_id' => $user->id,
                'interaction_type' => 'slider_response',
            ],
            [
                'data' => ['value' => (float) $request->value],
            ]
        );

        return $this->_getSliderResults($story, $user->id);
    }

    /**
     * Fetch slider results for a story.
     * Params: story_id
     */
    public function fetchSliderResults(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);

        $validator = Validator::make($request->all(), [
            'story_id' => 'required|exists:stories,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $story = Story::find($request->story_id);
        return $this->_getSliderResults($story, $user->id);
    }

    /**
     * Helper: Build slider results response.
     */
    private function _getSliderResults(Story $story, int $userId)
    {
        $responses = StoryInteraction::where('story_id', $story->id)
            ->where('interaction_type', 'slider_response')
            ->get();

        $totalResponses = $responses->count();
        $myValue = null;
        $sum = 0.0;

        foreach ($responses as $response) {
            $val = (float) ($response->data['value'] ?? 0);
            $sum += $val;
            if ($response->user_id === $userId) {
                $myValue = $val;
            }
        }

        $average = $totalResponses > 0 ? round($sum / $totalResponses, 3) : 0;

        return GlobalFunction::sendDataResponse(true, 'Slider results', [
            'total_responses' => $totalResponses,
            'average' => $average,
            'my_value' => $myValue,
        ]);
    }

    /**
     * Helper: Build quiz results response.
     */
    private function _getQuizResults(Story $story, int $userId)
    {
        $stickerData = json_decode($story->sticker_data, true);
        $options = $stickerData['options'] ?? [];
        $correctIndex = (int) ($stickerData['correct_index'] ?? 0);

        $answers = StoryInteraction::where('story_id', $story->id)
            ->where('interaction_type', 'quiz_answer')
            ->get();

        $totalAnswers = $answers->count();
        $answerCounts = array_fill(0, count($options), 0);
        $myAnswer = null;

        foreach ($answers as $answer) {
            $optionIndex = $answer->data['option_index'] ?? -1;
            if ($optionIndex >= 0 && $optionIndex < count($options)) {
                $answerCounts[$optionIndex]++;
            }
            if ($answer->user_id === $userId) {
                $myAnswer = $optionIndex;
            }
        }

        $results = [];
        for ($i = 0; $i < count($options); $i++) {
            $results[] = [
                'option' => $options[$i],
                'count' => $answerCounts[$i],
                'percentage' => $totalAnswers > 0 ? round(($answerCounts[$i] / $totalAnswers) * 100, 1) : 0,
                'is_correct' => $i === $correctIndex,
            ];
        }

        return GlobalFunction::sendDataResponse(true, 'Quiz results', [
            'total_answers' => $totalAnswers,
            'correct_index' => $correctIndex,
            'my_answer' => $myAnswer,
            'results' => $results,
        ]);
    }

    /**
     * Subscribe to a countdown sticker to get a reminder.
     * Params: story_id
     */
    public function subscribeCountdown(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);

        $validator = Validator::make($request->all(), [
            'story_id' => 'required|exists:stories,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $story = Story::find($request->story_id);
        $stickerData = json_decode($story->sticker_data, true);

        if (!$stickerData || ($stickerData['type'] ?? '') !== 'countdown') {
            return response()->json(['status' => false, 'message' => 'This story does not have a countdown sticker']);
        }

        StoryInteraction::updateOrCreate(
            [
                'story_id' => $request->story_id,
                'user_id' => $user->id,
                'interaction_type' => 'countdown_subscribe',
            ],
            [
                'data' => ['subscribed' => true],
            ]
        );

        return $this->_getCountdownInfo($story, $user->id);
    }

    /**
     * Unsubscribe from a countdown sticker.
     * Params: story_id
     */
    public function unsubscribeCountdown(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);

        $validator = Validator::make($request->all(), [
            'story_id' => 'required|exists:stories,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $story = Story::find($request->story_id);

        StoryInteraction::where('story_id', $request->story_id)
            ->where('user_id', $user->id)
            ->where('interaction_type', 'countdown_subscribe')
            ->delete();

        return $this->_getCountdownInfo($story, $user->id);
    }

    /**
     * Fetch countdown info for a story.
     * Params: story_id
     */
    public function fetchCountdownInfo(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);

        $validator = Validator::make($request->all(), [
            'story_id' => 'required|exists:stories,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $story = Story::find($request->story_id);
        return $this->_getCountdownInfo($story, $user->id);
    }

    /**
     * Send countdown reminder notifications (called by scheduler).
     */
    public function processCountdownReminders()
    {
        $now = now();
        $windowEnd = $now->copy()->addMinutes(1);

        // Find stories with countdown stickers ending in the next 1 minute
        $stories = Story::whereNotNull('sticker_data')
            ->where('sticker_data', 'like', '%countdown%')
            ->get()
            ->filter(function ($story) use ($now, $windowEnd) {
                $data = json_decode($story->sticker_data, true);
                if (($data['type'] ?? '') !== 'countdown') return false;
                $endTime = \Carbon\Carbon::parse($data['end_time'] ?? '');
                return $endTime->between($now, $windowEnd);
            });

        foreach ($stories as $story) {
            $stickerData = json_decode($story->sticker_data, true);
            $title = $stickerData['title'] ?? 'Countdown';

            $subscribers = StoryInteraction::where('story_id', $story->id)
                ->where('interaction_type', 'countdown_subscribe')
                ->get();

            foreach ($subscribers as $sub) {
                $toUser = \App\Models\Users::find($sub->user_id);
                if ($toUser) {
                    GlobalFunction::initiatePushNotification(
                        true,
                        true,
                        $toUser,
                        "Countdown ended: $title",
                        'The countdown you subscribed to has ended!',
                        ['type' => 'countdown_end', 'story_id' => $story->id]
                    );
                }
            }

            // Mark as processed by removing subscribers
            StoryInteraction::where('story_id', $story->id)
                ->where('interaction_type', 'countdown_subscribe')
                ->delete();
        }

        return response()->json(['status' => true, 'message' => 'Processed']);
    }

    /**
     * Helper: Build countdown info response.
     */
    private function _getCountdownInfo(Story $story, int $userId)
    {
        $stickerData = json_decode($story->sticker_data, true);

        $subscriberCount = StoryInteraction::where('story_id', $story->id)
            ->where('interaction_type', 'countdown_subscribe')
            ->count();

        $mySubscription = StoryInteraction::where('story_id', $story->id)
            ->where('user_id', $userId)
            ->where('interaction_type', 'countdown_subscribe')
            ->exists();

        return GlobalFunction::sendDataResponse(true, 'Countdown info', [
            'title' => $stickerData['title'] ?? '',
            'end_time' => $stickerData['end_time'] ?? '',
            'subscriber_count' => $subscriberCount,
            'is_subscribed' => $mySubscription,
        ]);
    }

    /**
     * Create an "Add Yours" chain (called when story with add_yours sticker is created).
     * Params: story_id, prompt
     */
    public function createAddYoursChain(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);

        $validator = Validator::make($request->all(), [
            'story_id' => 'required|exists:stories,id',
            'prompt' => 'required|string|max:300',
        ]);

        if ($validator->fails()) {
            return GlobalFunction::sendSimpleResponse(false, $validator->errors()->first());
        }

        $story = Story::find($request->story_id);
        if ($story->user_id !== $user->id) {
            return GlobalFunction::sendSimpleResponse(false, 'Unauthorized');
        }

        $chain = StoryChain::create([
            'prompt' => $request->prompt,
            'creator_id' => $user->id,
            'origin_story_id' => $story->id,
            'participant_count' => 1,
        ]);

        // Creator is first participant
        StoryChainParticipant::create([
            'chain_id' => $chain->id,
            'story_id' => $story->id,
            'user_id' => $user->id,
        ]);

        // Update the story's sticker_data to include chain_id
        $stickerData = json_decode($story->sticker_data, true) ?? [];
        $stickerData['chain_id'] = $chain->id;
        $story->sticker_data = json_encode($stickerData);
        $story->save();

        return GlobalFunction::sendDataResponse(true, 'Chain created', [
            'chain_id' => $chain->id,
            'prompt' => $chain->prompt,
            'participant_count' => $chain->participant_count,
        ]);
    }

    /**
     * Participate in an "Add Yours" chain.
     * Params: chain_id, story_id (the new story the user created)
     */
    public function participateInChain(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);

        $validator = Validator::make($request->all(), [
            'chain_id' => 'required',
            'story_id' => 'required|exists:stories,id',
        ]);

        if ($validator->fails()) {
            return GlobalFunction::sendSimpleResponse(false, $validator->errors()->first());
        }

        $chain = StoryChain::find($request->chain_id);
        if (!$chain || !$chain->is_active) {
            return GlobalFunction::sendSimpleResponse(false, 'Chain not found or inactive');
        }

        // Check if already participated
        $existing = StoryChainParticipant::where('chain_id', $chain->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existing) {
            return GlobalFunction::sendSimpleResponse(false, 'Already participated in this chain');
        }

        $story = Story::find($request->story_id);
        if ($story->user_id !== $user->id) {
            return GlobalFunction::sendSimpleResponse(false, 'Unauthorized');
        }

        StoryChainParticipant::create([
            'chain_id' => $chain->id,
            'story_id' => $story->id,
            'user_id' => $user->id,
        ]);

        $chain->increment('participant_count');

        // Update the participant's story sticker_data to include chain_id
        $stickerData = json_decode($story->sticker_data, true) ?? [];
        $stickerData['chain_id'] = $chain->id;
        $stickerData['type'] = 'add_yours';
        $stickerData['prompt'] = $chain->prompt;
        $story->sticker_data = json_encode($stickerData);
        $story->save();

        return GlobalFunction::sendDataResponse(true, 'Participated in chain', [
            'chain_id' => $chain->id,
            'participant_count' => $chain->participant_count,
        ]);
    }

    /**
     * Fetch chain info and participants.
     * Params: chain_id
     */
    public function fetchChainInfo(Request $request)
    {
        $token = $request->header('authtoken');
        GlobalFunction::getUserFromAuthToken($token);

        $validator = Validator::make($request->all(), [
            'chain_id' => 'required',
        ]);

        if ($validator->fails()) {
            return GlobalFunction::sendSimpleResponse(false, $validator->errors()->first());
        }

        $chain = StoryChain::find($request->chain_id);
        if (!$chain) {
            return GlobalFunction::sendSimpleResponse(false, 'Chain not found');
        }

        $participants = StoryChainParticipant::where('chain_id', $chain->id)
            ->with(['user:' . Constants::userPublicFields])
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get()
            ->map(function ($p) {
                return [
                    'user' => $p->user,
                    'story_id' => $p->story_id,
                    'created_at' => $p->created_at?->toISOString(),
                ];
            });

        return GlobalFunction::sendDataResponse(true, 'Chain info', [
            'chain_id' => $chain->id,
            'prompt' => $chain->prompt,
            'participant_count' => $chain->participant_count,
            'creator' => $chain->creator?->only(explode(',', Constants::userPublicFields)),
            'participants' => $participants,
        ]);
    }

    /**
     * Helper: Build poll results response.
     */
    private function _getPollResults(Story $story, int $userId)
    {
        $stickerData = json_decode($story->sticker_data, true);
        $options = $stickerData['options'] ?? [];

        $votes = StoryInteraction::where('story_id', $story->id)
            ->where('interaction_type', 'poll_vote')
            ->get();

        $totalVotes = $votes->count();
        $voteCounts = array_fill(0, count($options), 0);
        $myVote = null;

        foreach ($votes as $vote) {
            $optionIndex = $vote->data['option_index'] ?? -1;
            if ($optionIndex >= 0 && $optionIndex < count($options)) {
                $voteCounts[$optionIndex]++;
            }
            if ($vote->user_id === $userId) {
                $myVote = $optionIndex;
            }
        }

        $results = [];
        for ($i = 0; $i < count($options); $i++) {
            $results[] = [
                'option' => $options[$i],
                'votes' => $voteCounts[$i],
                'percentage' => $totalVotes > 0 ? round(($voteCounts[$i] / $totalVotes) * 100, 1) : 0,
            ];
        }

        return GlobalFunction::sendDataResponse(true, 'Poll results', [
            'total_votes' => $totalVotes,
            'my_vote' => $myVote,
            'results' => $results,
        ]);
    }
}
