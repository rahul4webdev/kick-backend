<?php

namespace App\Http\Controllers;

use App\Models\BroadcastChannel;
use App\Models\BroadcastMember;
use App\Models\GlobalFunction;
use Illuminate\Http\Request;

class BroadcastController extends Controller
{
    /**
     * Create a broadcast channel.
     */
    public function createChannel(Request $request)
    {
        $user = GlobalFunction::getUser($request);
        if (!$user) return GlobalFunction::sendSimpleResponse(false, 'Unauthorized');

        $request->validate([
            'name' => 'required|string|max:200',
            'description' => 'nullable|string|max:1000',
            'image' => 'nullable|string|max:500',
        ]);

        $channel = BroadcastChannel::create([
            'creator_user_id' => $user->id,
            'name' => $request->name,
            'description' => $request->description,
            'image' => $request->image,
            'member_count' => 1,
        ]);

        // Creator auto-joins as first member
        BroadcastMember::create([
            'channel_id' => $channel->id,
            'user_id' => $user->id,
        ]);

        $channel->load('creator');

        return GlobalFunction::sendDataResponse(true, 'Channel created', $this->formatChannel($channel, $user->id));
    }

    /**
     * Update channel details. Only creator can update.
     */
    public function updateChannel(Request $request)
    {
        $user = GlobalFunction::getUser($request);
        if (!$user) return GlobalFunction::sendSimpleResponse(false, 'Unauthorized');

        $request->validate([
            'channel_id' => 'required|integer',
            'name' => 'nullable|string|max:200',
            'description' => 'nullable|string|max:1000',
            'image' => 'nullable|string|max:500',
        ]);

        $channel = BroadcastChannel::where('id', $request->channel_id)
            ->where('creator_user_id', $user->id)
            ->first();

        if (!$channel) {
            return GlobalFunction::sendSimpleResponse(false, 'Channel not found or not authorized');
        }

        if ($request->has('name') && $request->name) {
            $channel->name = $request->name;
        }
        if ($request->has('description')) {
            $channel->description = $request->description;
        }
        if ($request->has('image')) {
            $channel->image = $request->image;
        }

        $channel->save();
        $channel->load('creator');

        return GlobalFunction::sendDataResponse(true, 'Channel updated', $this->formatChannel($channel, $user->id));
    }

    /**
     * Delete a channel. Only creator can delete.
     */
    public function deleteChannel(Request $request)
    {
        $user = GlobalFunction::getUser($request);
        if (!$user) return GlobalFunction::sendSimpleResponse(false, 'Unauthorized');

        $request->validate([
            'channel_id' => 'required|integer',
        ]);

        $channel = BroadcastChannel::where('id', $request->channel_id)
            ->where('creator_user_id', $user->id)
            ->first();

        if (!$channel) {
            return GlobalFunction::sendSimpleResponse(false, 'Channel not found or not authorized');
        }

        $channel->delete();

        return GlobalFunction::sendSimpleResponse(true, 'Channel deleted');
    }

    /**
     * Join a broadcast channel.
     */
    public function joinChannel(Request $request)
    {
        $user = GlobalFunction::getUser($request);
        if (!$user) return GlobalFunction::sendSimpleResponse(false, 'Unauthorized');

        $request->validate([
            'channel_id' => 'required|integer',
        ]);

        $channel = BroadcastChannel::where('id', $request->channel_id)
            ->where('is_active', true)
            ->first();

        if (!$channel) {
            return GlobalFunction::sendSimpleResponse(false, 'Channel not found');
        }

        $existing = BroadcastMember::where('channel_id', $channel->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existing) {
            return GlobalFunction::sendSimpleResponse(false, 'Already a member');
        }

        BroadcastMember::create([
            'channel_id' => $channel->id,
            'user_id' => $user->id,
        ]);

        $channel->increment('member_count');

        return GlobalFunction::sendSimpleResponse(true, 'Joined channel');
    }

    /**
     * Leave a broadcast channel.
     */
    public function leaveChannel(Request $request)
    {
        $user = GlobalFunction::getUser($request);
        if (!$user) return GlobalFunction::sendSimpleResponse(false, 'Unauthorized');

        $request->validate([
            'channel_id' => 'required|integer',
        ]);

        $channel = BroadcastChannel::find($request->channel_id);
        if (!$channel) {
            return GlobalFunction::sendSimpleResponse(false, 'Channel not found');
        }

        // Creator cannot leave their own channel
        if ($channel->creator_user_id == $user->id) {
            return GlobalFunction::sendSimpleResponse(false, 'Creator cannot leave the channel');
        }

        $deleted = BroadcastMember::where('channel_id', $channel->id)
            ->where('user_id', $user->id)
            ->delete();

        if ($deleted) {
            $channel->decrement('member_count');
        }

        return GlobalFunction::sendSimpleResponse(true, 'Left channel');
    }

    /**
     * Toggle mute/unmute notifications for a channel.
     */
    public function toggleMute(Request $request)
    {
        $user = GlobalFunction::getUser($request);
        if (!$user) return GlobalFunction::sendSimpleResponse(false, 'Unauthorized');

        $request->validate([
            'channel_id' => 'required|integer',
        ]);

        $member = BroadcastMember::where('channel_id', $request->channel_id)
            ->where('user_id', $user->id)
            ->first();

        if (!$member) {
            return GlobalFunction::sendSimpleResponse(false, 'Not a member');
        }

        $member->is_muted = !$member->is_muted;
        $member->save();

        return GlobalFunction::sendDataResponse(true, 'Mute toggled', [
            'is_muted' => $member->is_muted,
        ]);
    }

    /**
     * Fetch channels I've joined.
     */
    public function fetchMyChannels(Request $request)
    {
        $user = GlobalFunction::getUser($request);
        if (!$user) return GlobalFunction::sendSimpleResponse(false, 'Unauthorized');

        $limit = $request->input('limit', 20);
        $offset = $request->input('offset', 0);

        $memberChannelIds = BroadcastMember::where('user_id', $user->id)
            ->pluck('channel_id');

        $channels = BroadcastChannel::whereIn('id', $memberChannelIds)
            ->where('is_active', true)
            ->with('creator')
            ->orderBy('updated_at', 'desc')
            ->offset($offset)
            ->limit($limit)
            ->get();

        $formatted = $channels->map(fn($ch) => $this->formatChannel($ch, $user->id));

        return GlobalFunction::sendDataResponse(true, 'Success', $formatted);
    }

    /**
     * Fetch channels created by a user (for profile view).
     */
    public function fetchUserChannels(Request $request)
    {
        $user = GlobalFunction::getUser($request);
        if (!$user) return GlobalFunction::sendSimpleResponse(false, 'Unauthorized');

        $request->validate([
            'user_id' => 'required|integer',
        ]);

        $channels = BroadcastChannel::where('creator_user_id', $request->user_id)
            ->where('is_active', true)
            ->with('creator')
            ->orderBy('created_at', 'desc')
            ->get();

        $formatted = $channels->map(fn($ch) => $this->formatChannel($ch, $user->id));

        return GlobalFunction::sendDataResponse(true, 'Success', $formatted);
    }

    /**
     * Fetch channel details.
     */
    public function fetchChannelDetails(Request $request)
    {
        $user = GlobalFunction::getUser($request);
        if (!$user) return GlobalFunction::sendSimpleResponse(false, 'Unauthorized');

        $request->validate([
            'channel_id' => 'required|integer',
        ]);

        $channel = BroadcastChannel::where('id', $request->channel_id)
            ->where('is_active', true)
            ->with('creator')
            ->first();

        if (!$channel) {
            return GlobalFunction::sendSimpleResponse(false, 'Channel not found');
        }

        return GlobalFunction::sendDataResponse(true, 'Success', $this->formatChannel($channel, $user->id));
    }

    /**
     * Fetch channel members (paginated).
     */
    public function fetchChannelMembers(Request $request)
    {
        $user = GlobalFunction::getUser($request);
        if (!$user) return GlobalFunction::sendSimpleResponse(false, 'Unauthorized');

        $request->validate([
            'channel_id' => 'required|integer',
        ]);

        $limit = $request->input('limit', 30);
        $offset = $request->input('offset', 0);

        $members = BroadcastMember::where('channel_id', $request->channel_id)
            ->with('user')
            ->orderBy('joined_at', 'asc')
            ->offset($offset)
            ->limit($limit)
            ->get();

        $formatted = $members->map(function ($m) {
            return [
                'user_id' => $m->user_id,
                'username' => $m->user->username ?? '',
                'fullname' => $m->user->full_name ?? '',
                'profile_photo' => $m->user->profile ?? '',
                'is_verify' => $m->user->is_verify ?? 0,
                'joined_at' => $m->joined_at,
            ];
        });

        return GlobalFunction::sendDataResponse(true, 'Success', $formatted);
    }

    /**
     * Search/discover broadcast channels.
     */
    public function searchChannels(Request $request)
    {
        $user = GlobalFunction::getUser($request);
        if (!$user) return GlobalFunction::sendSimpleResponse(false, 'Unauthorized');

        $query = $request->input('query', '');
        $limit = $request->input('limit', 20);
        $offset = $request->input('offset', 0);

        $builder = BroadcastChannel::where('is_active', true)
            ->with('creator');

        if (!empty($query)) {
            $builder->where('name', 'ilike', "%{$query}%");
        }

        $channels = $builder->orderBy('member_count', 'desc')
            ->offset($offset)
            ->limit($limit)
            ->get();

        $formatted = $channels->map(fn($ch) => $this->formatChannel($ch, $user->id));

        return GlobalFunction::sendDataResponse(true, 'Success', $formatted);
    }

    /**
     * Format channel for API response.
     */
    private function formatChannel(BroadcastChannel $channel, int $userId): array
    {
        $isMember = BroadcastMember::where('channel_id', $channel->id)
            ->where('user_id', $userId)
            ->exists();

        $isMuted = false;
        if ($isMember) {
            $member = BroadcastMember::where('channel_id', $channel->id)
                ->where('user_id', $userId)
                ->first();
            $isMuted = $member->is_muted ?? false;
        }

        return [
            'id' => $channel->id,
            'name' => $channel->name,
            'description' => $channel->description,
            'image' => $channel->image,
            'member_count' => $channel->member_count,
            'creator_user_id' => $channel->creator_user_id,
            'creator' => $channel->creator ? [
                'id' => $channel->creator->id,
                'username' => $channel->creator->username,
                'fullname' => $channel->creator->full_name,
                'profile_photo' => $channel->creator->profile,
                'is_verify' => $channel->creator->is_verify,
            ] : null,
            'is_member' => $isMember,
            'is_muted' => $isMuted,
            'is_creator' => $channel->creator_user_id == $userId,
            'created_at' => $channel->created_at?->toISOString(),
            'updated_at' => $channel->updated_at?->toISOString(),
        ];
    }
}
