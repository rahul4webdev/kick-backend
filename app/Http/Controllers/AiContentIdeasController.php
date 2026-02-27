<?php

namespace App\Http\Controllers;

use App\Models\GlobalFunction;
use App\Models\GlobalSettings;
use App\Services\GeminiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AiContentIdeasController extends Controller
{
    public function generateIdeas(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));
        if ($user->is_freez == 1) {
            return response()->json(['status' => false, 'message' => 'Account frozen']);
        }

        $request->validate([
            'niche' => 'nullable|string|max:200',
            'count' => 'nullable|integer|min:1|max:10',
        ]);

        $settings = GlobalSettings::getCached();

        if (!$settings->ai_content_ideas_enabled) {
            return response()->json(['status' => false, 'message' => 'Content ideas feature is currently disabled']);
        }

        if (empty($settings->ai_api_key)) {
            return response()->json(['status' => false, 'message' => 'AI service not configured']);
        }

        $count = $request->count ?? 5;
        $niche = $request->niche ?? 'general entertainment';

        // Fetch trending hashtags for context
        $trendingHashtags = DB::table('tbl_hashtag')
            ->orderByDesc('hashtag_count')
            ->limit(20)
            ->pluck('hashtag_name')
            ->toArray();

        $trendingContext = !empty($trendingHashtags)
            ? 'Current trending hashtags on the platform: ' . implode(', ', $trendingHashtags)
            : '';

        $systemPrompt = "You are a social media content strategist for a short-video platform (like TikTok/Instagram Reels). Generate creative, engaging content ideas. {$trendingContext}

Return ONLY a JSON array (no markdown, no code blocks) with exactly {$count} objects, each with these fields:
- \"title\": Short catchy title (max 50 chars)
- \"description\": Brief description of the content idea (max 150 chars)
- \"format\": One of [\"reel\", \"story\", \"photo\", \"carousel\", \"live\"]
- \"hashtags\": Array of 3-5 relevant hashtags (without # prefix)
- \"hook\": A compelling opening line or hook for the video (max 80 chars)
- \"difficulty\": One of [\"easy\", \"medium\", \"hard\"]";

        try {
            $result = GeminiService::generateContent($systemPrompt, [
                ['role' => 'user', 'content' => "Generate {$count} content ideas for a creator in the \"{$niche}\" niche."],
            ], 2048);

            if ($result['success']) {
                $text = $result['text'] ?? '[]';

                // Try to parse JSON from the response
                $ideas = json_decode($text, true);
                if (!is_array($ideas)) {
                    // Try extracting JSON from potential markdown code block
                    if (preg_match('/\[[\s\S]*\]/', $text, $matches)) {
                        $ideas = json_decode($matches[0], true);
                    }
                }

                if (!is_array($ideas)) {
                    $ideas = [];
                }

                return response()->json([
                    'status' => true,
                    'message' => 'Success',
                    'data' => $ideas,
                ]);
            }

            return response()->json(['status' => false, 'message' => $result['error'] ?? 'AI service error']);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'AI service unavailable']);
        }
    }

    public function fetchTrendingTopics(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));
        if ($user->is_freez == 1) {
            return response()->json(['status' => false, 'message' => 'Account frozen']);
        }

        // Fetch trending hashtags
        $hashtags = DB::table('tbl_hashtag')
            ->orderByDesc('hashtag_count')
            ->limit(30)
            ->get(['id', 'hashtag_name', 'hashtag_count']);

        // Fetch trending sounds/music
        $sounds = DB::table('tbl_sounds')
            ->orderByDesc('use_count')
            ->limit(10)
            ->get(['id', 'title', 'artist', 'use_count']);

        return response()->json([
            'status' => true,
            'message' => 'Success',
            'data' => [
                'hashtags' => $hashtags,
                'sounds' => $sounds,
            ],
        ]);
    }
}
