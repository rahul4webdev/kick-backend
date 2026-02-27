<?php

namespace App\Http\Controllers;

use App\Models\GlobalFunction;
use App\Models\LocationReview;
use App\Models\Users;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LocationReviewController extends Controller
{
    // ─── Submit Location Review ───────────────────────────────────

    public function submitReview(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));

        $rules = [
            'place_title' => 'required|string|max:200',
            'place_lat' => 'required|numeric',
            'place_lon' => 'required|numeric',
            'rating' => 'required|integer|min:1|max:5',
            'review_text' => 'nullable|string|max:2000',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return GlobalFunction::sendSimpleResponse(false, $validator->errors()->first());
        }

        // Check if already reviewed this location
        $existing = LocationReview::where('user_id', $user->id)
            ->where('place_title', $request->place_title)
            ->where('place_lat', $request->place_lat)
            ->where('place_lon', $request->place_lon)
            ->first();

        if ($existing) {
            return GlobalFunction::sendSimpleResponse(false, 'You have already reviewed this location');
        }

        $review = LocationReview::create([
            'user_id' => $user->id,
            'place_title' => $request->place_title,
            'place_lat' => $request->place_lat,
            'place_lon' => $request->place_lon,
            'rating' => $request->rating,
            'review_text' => $request->review_text,
        ]);

        // Handle review photos
        if ($request->hasFile('photos')) {
            $photos = [];
            $files = is_array($request->file('photos')) ? $request->file('photos') : [$request->file('photos')];
            foreach ($files as $file) {
                $photos[] = GlobalFunction::saveFileAndGivePath($file);
            }
            $review->photos = $photos;
            $review->save();
        }

        $review->reviewer = Users::where('id', $user->id)->first(['id', 'username', 'fullname', 'profile_photo']);

        return GlobalFunction::sendDataResponse(true, 'Review submitted', $review);
    }

    // ─── Fetch Location Reviews ───────────────────────────────────

    public function fetchLocationReviews(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));

        $rules = [
            'place_title' => 'required|string',
            'place_lat' => 'required|numeric',
            'place_lon' => 'required|numeric',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return GlobalFunction::sendSimpleResponse(false, $validator->errors()->first());
        }

        $limit = $request->limit ?? 20;

        // Find reviews within 100m of the specified location
        $query = LocationReview::whereRaw(
            '6371000 * acos(cos(radians(?)) * cos(radians(place_lat)) * cos(radians(place_lon) - radians(?)) + sin(radians(?)) * sin(radians(place_lat))) < 100',
            [$request->place_lat, $request->place_lon, $request->place_lat]
        )->orderByDesc('id')->limit($limit);

        if ($request->has('last_item_id') && $request->last_item_id) {
            $query->where('id', '<', $request->last_item_id);
        }

        $reviews = $query->get();

        $reviews->each(function ($r) {
            $r->reviewer = Users::where('id', $r->user_id)->first(['id', 'username', 'fullname', 'profile_photo']);
            if ($r->photos) {
                $r->photo_urls = array_map(fn($path) => GlobalFunction::generateFileUrl($path), $r->photos);
            }
        });

        // Calculate aggregate stats
        $stats = LocationReview::whereRaw(
            '6371000 * acos(cos(radians(?)) * cos(radians(place_lat)) * cos(radians(place_lon) - radians(?)) + sin(radians(?)) * sin(radians(place_lat))) < 100',
            [$request->place_lat, $request->place_lon, $request->place_lat]
        )->selectRaw('COUNT(*) as review_count, AVG(rating) as avg_rating')->first();

        return [
            'status' => true,
            'message' => '',
            'data' => [
                'reviews' => $reviews,
                'review_count' => (int) ($stats->review_count ?? 0),
                'avg_rating' => round($stats->avg_rating ?? 0, 1),
            ],
        ];
    }

    // ─── Fetch My Location Reviews ────────────────────────────────

    public function fetchMyReviews(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));

        $limit = $request->limit ?? 20;

        $query = LocationReview::where('user_id', $user->id)
            ->orderByDesc('id')
            ->limit($limit);

        if ($request->has('last_item_id') && $request->last_item_id) {
            $query->where('id', '<', $request->last_item_id);
        }

        $reviews = $query->get();

        $reviews->each(function ($r) {
            if ($r->photos) {
                $r->photo_urls = array_map(fn($path) => GlobalFunction::generateFileUrl($path), $r->photos);
            }
        });

        return GlobalFunction::sendDataResponse(true, '', $reviews);
    }

    // ─── Delete Location Review ───────────────────────────────────

    public function deleteReview(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));

        $review = LocationReview::where('id', $request->review_id)
            ->where('user_id', $user->id)
            ->first();

        if (!$review) {
            return GlobalFunction::sendSimpleResponse(false, 'Review not found');
        }

        $review->delete();

        return GlobalFunction::sendSimpleResponse(true, 'Review deleted');
    }

    // ─── Admin: List Location Reviews ─────────────────────────────

    public function listLocationReviews_Admin(Request $request)
    {
        $totalData = LocationReview::count();
        $totalFiltered = $totalData;
        $limit = $request->input('length');
        $start = $request->input('start');

        $query = LocationReview::with(['user:id,username,fullname,profile_photo']);

        if (!empty($request->input('search.value'))) {
            $search = $request->input('search.value');
            $query->where('place_title', 'ilike', "%$search%")
                ->orWhereHas('user', function ($q) use ($search) {
                    $q->where('username', 'ilike', "%$search%");
                });
            $totalFiltered = $query->count();
        }

        $records = $query->orderByDesc('id')->offset($start)->limit($limit)->get();

        $data = [];
        foreach ($records as $i => $record) {
            $stars = str_repeat('★', $record->rating) . str_repeat('☆', 5 - $record->rating);
            $data[] = [
                $start + $i + 1,
                $record->user->username ?? '-',
                $record->place_title,
                $stars,
                substr($record->review_text ?? '-', 0, 100),
                $record->created_at?->format('Y-m-d'),
            ];
        }

        return [
            'draw' => intval($request->input('draw')),
            'recordsTotal' => intval($totalData),
            'recordsFiltered' => intval($totalFiltered),
            'data' => $data,
        ];
    }
}
