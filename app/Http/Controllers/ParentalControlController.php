<?php

namespace App\Http\Controllers;

use App\Models\FamilyLink;
use App\Models\GlobalFunction;
use App\Models\Users;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ParentalControlController extends Controller
{
    // ─── Generate Pairing Code (Parent initiates) ─────────────────

    public function generatePairingCode(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));

        // Invalidate any existing unused pairing codes for this parent
        FamilyLink::where('parent_user_id', $user->id)
            ->where('status', FamilyLink::STATUS_PENDING)
            ->whereNotNull('pairing_code')
            ->delete();

        $code = strtoupper(Str::random(8));

        $link = FamilyLink::create([
            'parent_user_id' => $user->id,
            'teen_user_id' => 0, // placeholder until teen pairs
            'pairing_code' => $code,
            'status' => FamilyLink::STATUS_PENDING,
            'controls' => FamilyLink::defaultControls(),
        ]);

        return [
            'status' => true,
            'message' => 'Pairing code generated',
            'data' => [
                'pairing_code' => $code,
                'id' => $link->id,
            ],
        ];
    }

    // ─── Link with Pairing Code (Teen enters code) ───────────────

    public function linkWithCode(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));

        $code = $request->pairing_code;
        if (!$code) {
            return GlobalFunction::sendSimpleResponse(false, 'Pairing code is required');
        }

        $link = FamilyLink::where('pairing_code', $code)
            ->where('status', FamilyLink::STATUS_PENDING)
            ->first();

        if (!$link) {
            return GlobalFunction::sendSimpleResponse(false, 'Invalid or expired pairing code');
        }

        if ($link->parent_user_id == $user->id) {
            return GlobalFunction::sendSimpleResponse(false, 'You cannot pair with yourself');
        }

        // Check if already linked with this parent
        $existing = FamilyLink::where('parent_user_id', $link->parent_user_id)
            ->where('teen_user_id', $user->id)
            ->where('status', FamilyLink::STATUS_LINKED)
            ->first();

        if ($existing) {
            return GlobalFunction::sendSimpleResponse(false, 'Already linked with this parent account');
        }

        $link->update([
            'teen_user_id' => $user->id,
            'pairing_code' => null, // code is consumed
            'status' => FamilyLink::STATUS_LINKED,
        ]);

        $link->load(['parent:id,username,fullname,profile_photo,is_verify']);

        return [
            'status' => true,
            'message' => 'Accounts linked successfully',
            'data' => $link,
        ];
    }

    // ─── Unlink (Parent or Teen) ──────────────────────────────────

    public function unlinkAccount(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));

        $link = FamilyLink::where('id', $request->link_id)
            ->where('status', FamilyLink::STATUS_LINKED)
            ->where(function ($q) use ($user) {
                $q->where('parent_user_id', $user->id)
                  ->orWhere('teen_user_id', $user->id);
            })
            ->first();

        if (!$link) {
            return GlobalFunction::sendSimpleResponse(false, 'Family link not found');
        }

        $link->update(['status' => FamilyLink::STATUS_UNLINKED]);

        return GlobalFunction::sendSimpleResponse(true, 'Account unlinked successfully');
    }

    // ─── Update Controls (Parent only) ────────────────────────────

    public function updateControls(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));

        $link = FamilyLink::where('id', $request->link_id)
            ->where('parent_user_id', $user->id)
            ->where('status', FamilyLink::STATUS_LINKED)
            ->first();

        if (!$link) {
            return GlobalFunction::sendSimpleResponse(false, 'Family link not found');
        }

        $controls = $link->controls ?? FamilyLink::defaultControls();

        // Merge only valid control keys
        $allowedKeys = [
            'daily_screen_time_min',
            'dm_restricted',
            'live_restricted',
            'discover_restricted',
            'purchase_restricted',
            'live_stream_restricted',
            'activity_reports',
        ];

        $newControls = $request->controls ?? [];
        foreach ($allowedKeys as $key) {
            if (array_key_exists($key, $newControls)) {
                $controls[$key] = $newControls[$key];
            }
        }

        $link->update(['controls' => $controls]);

        $link->load(['teen:id,username,fullname,profile_photo,is_verify']);

        return [
            'status' => true,
            'message' => 'Parental controls updated',
            'data' => $link,
        ];
    }

    // ─── Fetch Linked Accounts (Parent: fetch teens, Teen: fetch parents) ──

    public function fetchLinkedAccounts(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));

        // As parent: fetch linked teens
        $teens = FamilyLink::where('parent_user_id', $user->id)
            ->where('status', FamilyLink::STATUS_LINKED)
            ->with(['teen:id,username,fullname,profile_photo,is_verify'])
            ->orderByDesc('id')
            ->get();

        // As teen: fetch linked parents
        $parents = FamilyLink::where('teen_user_id', $user->id)
            ->where('status', FamilyLink::STATUS_LINKED)
            ->with(['parent:id,username,fullname,profile_photo,is_verify'])
            ->orderByDesc('id')
            ->get();

        return [
            'status' => true,
            'message' => '',
            'data' => [
                'linked_teens' => $teens,
                'linked_parents' => $parents,
            ],
        ];
    }

    // ─── Fetch My Controls (Teen sees their own restrictions) ─────

    public function fetchMyControls(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));

        $links = FamilyLink::where('teen_user_id', $user->id)
            ->where('status', FamilyLink::STATUS_LINKED)
            ->with(['parent:id,username,fullname,profile_photo,is_verify'])
            ->get();

        // Merge controls from all parents (most restrictive wins)
        $mergedControls = FamilyLink::defaultControls();
        foreach ($links as $link) {
            $c = $link->controls ?? [];
            if (isset($c['daily_screen_time_min']) && $c['daily_screen_time_min'] < $mergedControls['daily_screen_time_min']) {
                $mergedControls['daily_screen_time_min'] = $c['daily_screen_time_min'];
            }
            if (!empty($c['dm_restricted'])) $mergedControls['dm_restricted'] = true;
            if (!empty($c['live_restricted'])) $mergedControls['live_restricted'] = true;
            if (!empty($c['discover_restricted'])) $mergedControls['discover_restricted'] = true;
            if (!empty($c['purchase_restricted'])) $mergedControls['purchase_restricted'] = true;
            if (!empty($c['live_stream_restricted'])) $mergedControls['live_stream_restricted'] = true;
        }

        return [
            'status' => true,
            'message' => '',
            'data' => [
                'controls' => $mergedControls,
                'linked_parents' => $links,
                'is_supervised' => $links->count() > 0,
            ],
        ];
    }

    // ─── Fetch Activity Report (Parent views teen's activity) ─────

    public function fetchActivityReport(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));

        $link = FamilyLink::where('id', $request->link_id)
            ->where('parent_user_id', $user->id)
            ->where('status', FamilyLink::STATUS_LINKED)
            ->first();

        if (!$link) {
            return GlobalFunction::sendSimpleResponse(false, 'Family link not found');
        }

        $teen = Users::find($link->teen_user_id);
        if (!$teen) {
            return GlobalFunction::sendSimpleResponse(false, 'Teen account not found');
        }

        // Basic activity summary
        $report = [
            'teen' => [
                'id' => $teen->id,
                'username' => $teen->username,
                'fullname' => $teen->fullname,
                'profile_photo' => $teen->profile_photo,
            ],
            'total_posts' => $teen->posts()->count(),
            'follower_count' => $teen->follower_count ?? 0,
            'following_count' => $teen->following_count ?? 0,
            'controls' => $link->controls,
        ];

        return [
            'status' => true,
            'message' => '',
            'data' => $report,
        ];
    }

    // ─── Admin: List Family Links ─────────────────────────────────

    public function listFamilyLinks_Admin(Request $request)
    {
        $totalData = FamilyLink::count();
        $totalFiltered = $totalData;
        $limit = $request->input('length');
        $start = $request->input('start');

        $query = FamilyLink::with([
            'parent:id,username,fullname,profile_photo',
            'teen:id,username,fullname,profile_photo',
        ]);

        if (!empty($request->input('search.value'))) {
            $search = $request->input('search.value');
            $query->whereHas('parent', function ($q) use ($search) {
                $q->where('username', 'ilike', "%$search%");
            })->orWhereHas('teen', function ($q) use ($search) {
                $q->where('username', 'ilike', "%$search%");
            });
            $totalFiltered = $query->count();
        }

        $records = $query->orderByDesc('id')->offset($start)->limit($limit)->get();

        $data = [];
        foreach ($records as $i => $record) {
            $statusLabel = FamilyLink::statusLabel($record->status);
            $statusClass = match ($record->status) {
                0 => 'warning',
                1 => 'success',
                2 => 'danger',
                default => 'secondary',
            };

            $data[] = [
                $start + $i + 1,
                $record->parent->username ?? '-',
                $record->teen->username ?? '-',
                '<span class="badge bg-' . $statusClass . '">' . $statusLabel . '</span>',
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
