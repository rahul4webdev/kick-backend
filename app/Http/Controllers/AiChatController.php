<?php

namespace App\Http\Controllers;

use App\Models\AiChatMessage;
use App\Models\GlobalFunction;
use App\Models\GlobalSettings;
use App\Services\GeminiService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AiChatController extends Controller
{
    public function sendMessage(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));
        if ($user->is_freez == 1) {
            return response()->json(['status' => false, 'message' => 'Account frozen']);
        }

        $request->validate([
            'message' => 'required|string|max:2000',
            'session_id' => 'nullable|string|max:100',
        ]);

        $settings = GlobalSettings::getCached();

        if (!$settings->ai_chatbot_enabled) {
            return response()->json(['status' => false, 'message' => 'AI chatbot is currently disabled']);
        }

        if (empty($settings->ai_api_key)) {
            return response()->json(['status' => false, 'message' => 'AI service not configured']);
        }

        $sessionId = $request->session_id ?? Str::uuid()->toString();

        // Fetch recent context (last 10 messages from this session)
        $contextMessages = AiChatMessage::where('user_id', $user->id)
            ->where('session_id', $sessionId)
            ->where('status', 'completed')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->reverse()
            ->values();

        // Build messages array
        $messages = [];
        foreach ($contextMessages as $msg) {
            $messages[] = ['role' => 'user', 'content' => $msg->user_message];
            if ($msg->ai_response) {
                $messages[] = ['role' => 'assistant', 'content' => $msg->ai_response];
            }
        }
        $messages[] = ['role' => 'user', 'content' => $request->message];

        // Create pending message record
        $chatMessage = AiChatMessage::create([
            'user_id' => $user->id,
            'session_id' => $sessionId,
            'user_message' => $request->message,
            'status' => 'pending',
        ]);

        $systemPrompt = $settings->ai_system_prompt ??
            'You are a friendly and helpful AI assistant inside a social media app. '
            . 'Keep responses concise and engaging. Help users with content ideas, '
            . 'captions, hashtags, and general questions. Be warm and supportive.';

        $result = GeminiService::generateContent($systemPrompt, $messages, 1024);

        if ($result['success']) {
            $aiText = $result['text'] ?? 'Sorry, I could not process your request.';

            $chatMessage->update([
                'ai_response' => $aiText,
                'status' => 'completed',
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Success',
                'data' => [
                    'id' => $chatMessage->id,
                    'session_id' => $sessionId,
                    'user_message' => $chatMessage->user_message,
                    'ai_response' => $aiText,
                    'created_at' => $chatMessage->created_at->toISOString(),
                ],
            ]);
        } else {
            $chatMessage->update(['status' => 'failed']);
            return response()->json(['status' => false, 'message' => $result['error'] ?? 'AI service error']);
        }
    }

    public function fetchHistory(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));
        if ($user->is_freez == 1) {
            return response()->json(['status' => false, 'message' => 'Account frozen']);
        }

        $sessionId = $request->session_id;
        $limit = $request->limit ?? 20;
        $beforeId = $request->before_id;

        $query = AiChatMessage::where('user_id', $user->id)
            ->where('status', 'completed');

        if ($sessionId) {
            $query->where('session_id', $sessionId);
        }

        if ($beforeId) {
            $query->where('id', '<', $beforeId);
        }

        $messages = $query->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values();

        return response()->json([
            'status' => true,
            'message' => 'Success',
            'data' => $messages->map(function ($msg) {
                return [
                    'id' => $msg->id,
                    'session_id' => $msg->session_id,
                    'user_message' => $msg->user_message,
                    'ai_response' => $msg->ai_response,
                    'created_at' => $msg->created_at->toISOString(),
                ];
            }),
        ]);
    }

    public function fetchSessions(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));
        if ($user->is_freez == 1) {
            return response()->json(['status' => false, 'message' => 'Account frozen']);
        }

        $sessions = AiChatMessage::where('user_id', $user->id)
            ->where('status', 'completed')
            ->selectRaw('session_id, MIN(user_message) as first_message, MAX(created_at) as last_active, COUNT(*) as message_count')
            ->groupBy('session_id')
            ->orderByDesc('last_active')
            ->limit(20)
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'Success',
            'data' => $sessions,
        ]);
    }

    public function clearHistory(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));
        if ($user->is_freez == 1) {
            return response()->json(['status' => false, 'message' => 'Account frozen']);
        }

        $sessionId = $request->session_id;

        $query = AiChatMessage::where('user_id', $user->id);
        if ($sessionId) {
            $query->where('session_id', $sessionId);
        }
        $query->delete();

        return response()->json(['status' => true, 'message' => 'Chat history cleared']);
    }

    public function fetchBotInfo(Request $request)
    {
        $settings = GlobalSettings::getCached();

        return response()->json([
            'status' => true,
            'message' => 'Success',
            'data' => [
                'enabled' => (bool) $settings->ai_chatbot_enabled,
                'bot_name' => $settings->ai_bot_name ?? 'AI Assistant',
                'bot_avatar' => $settings->ai_bot_avatar,
            ],
        ]);
    }
}
