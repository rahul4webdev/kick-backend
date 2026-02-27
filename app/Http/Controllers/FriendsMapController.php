<?php

namespace App\Http\Controllers;

use App\Models\CloseFriend;
use App\Models\Followers;
use App\Models\GlobalFunction;
use App\Models\UserLocation;
use Illuminate\Http\Request;

class FriendsMapController extends Controller
{
    // ─── Update My Location ─────────────────────────────────────
    public function updateLocation(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));

        if (!$request->lat || !$request->lon) {
            return GlobalFunction::sendSimpleResponse(false, 'lat and lon are required');
        }

        $location = UserLocation::updateOrCreate(
            ['user_id' => $user->id],
            [
                'lat' => $request->lat,
                'lon' => $request->lon,
                'location_updated_at' => now(),
            ]
        );

        return [
            'status' => true,
            'message' => 'Location updated',
            'data' => $location,
        ];
    }

    // ─── Toggle Location Sharing ────────────────────────────────
    public function toggleSharing(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));

        $location = UserLocation::firstOrCreate(
            ['user_id' => $user->id],
            ['lat' => 0, 'lon' => 0, 'is_sharing' => false]
        );

        $location->update(['is_sharing' => !$location->is_sharing]);

        return [
            'status' => true,
            'message' => $location->is_sharing
                ? 'Location sharing enabled'
                : 'Location sharing disabled',
            'data' => ['is_sharing' => $location->is_sharing],
        ];
    }

    // ─── Fetch My Sharing Status ────────────────────────────────
    public function fetchMyStatus(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));

        $location = UserLocation::where('user_id', $user->id)->first();

        return [
            'status' => true,
            'message' => '',
            'data' => [
                'is_sharing' => $location?->is_sharing ?? false,
                'lat' => $location?->lat,
                'lon' => $location?->lon,
                'location_updated_at' => $location?->location_updated_at,
            ],
        ];
    }

    // ─── Fetch Friends Locations ────────────────────────────────
    // Shows close friends who have location sharing enabled
    // Falls back to mutual followers if user has no close friends
    public function fetchFriendsLocations(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));

        // Get close friends (users I added as close friends)
        $closeFriendIds = CloseFriend::where('from_user_id', $user->id)
            ->pluck('to_user_id')
            ->toArray();

        $friendIds = $closeFriendIds;

        // If no close friends, fall back to mutual followers
        if (empty($friendIds)) {
            $followingIds = Followers::where('from_user_id', $user->id)
                ->pluck('to_user_id')
                ->toArray();
            $followerIds = Followers::where('to_user_id', $user->id)
                ->pluck('from_user_id')
                ->toArray();
            $friendIds = array_values(array_intersect($followingIds, $followerIds));
        }

        if (empty($friendIds)) {
            return [
                'status' => true,
                'message' => '',
                'data' => [],
            ];
        }

        // Fetch locations of friends who are sharing (updated in last 24 hours)
        $locations = UserLocation::whereIn('user_id', $friendIds)
            ->where('is_sharing', true)
            ->where('location_updated_at', '>=', now()->subHours(24))
            ->with(['user:id,username,fullname,profile_photo,is_verify'])
            ->get()
            ->map(function ($loc) {
                return [
                    'user_id' => $loc->user_id,
                    'lat' => $loc->lat,
                    'lon' => $loc->lon,
                    'location_updated_at' => $loc->location_updated_at,
                    'user' => $loc->user ? [
                        'id' => $loc->user->id,
                        'username' => $loc->user->username,
                        'fullname' => $loc->user->fullname,
                        'profile_photo' => $loc->user->profile_photo,
                        'is_verify' => $loc->user->is_verify,
                    ] : null,
                ];
            });

        return [
            'status' => true,
            'message' => '',
            'data' => $locations,
        ];
    }
}
