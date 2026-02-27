<?php

namespace App\Http\Controllers;

use App\Models\Constants;
use App\Models\GlobalFunction;
use App\Models\SharedAccess;
use App\Models\Users;
use Illuminate\Http\Request;

class SharedAccessController extends Controller
{
    // ─── Invite Team Member ──────────────────────────────────────

    public function inviteTeamMember(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));

        $memberId = $request->member_user_id;
        $role = $request->role ?? SharedAccess::ROLE_VIEWER;

        if ($memberId == $user->id) {
            return GlobalFunction::sendSimpleResponse(false, 'You cannot invite yourself');
        }

        $member = Users::find($memberId);
        if (!$member) {
            return GlobalFunction::sendSimpleResponse(false, 'User not found');
        }

        // Check max team members
        $existingCount = SharedAccess::where('account_user_id', $user->id)
            ->whereIn('status', [SharedAccess::STATUS_PENDING, SharedAccess::STATUS_ACCEPTED])
            ->count();

        if ($existingCount >= SharedAccess::MAX_TEAM_MEMBERS) {
            return GlobalFunction::sendSimpleResponse(false, 'Maximum ' . SharedAccess::MAX_TEAM_MEMBERS . ' team members allowed');
        }

        // Check if already invited
        $existing = SharedAccess::where('account_user_id', $user->id)
            ->where('member_user_id', $memberId)
            ->first();

        if ($existing) {
            if ($existing->status == SharedAccess::STATUS_DECLINED) {
                $existing->update([
                    'role' => $role,
                    'status' => SharedAccess::STATUS_PENDING,
                    'permissions' => SharedAccess::defaultPermissions($role),
                    'invited_by' => $user->id,
                ]);
                $access = $existing;
            } else {
                return GlobalFunction::sendSimpleResponse(false, 'User already invited or is a team member');
            }
        } else {
            $access = SharedAccess::create([
                'account_user_id' => $user->id,
                'member_user_id' => $memberId,
                'role' => $role,
                'status' => SharedAccess::STATUS_PENDING,
                'permissions' => SharedAccess::defaultPermissions($role),
                'invited_by' => $user->id,
            ]);
        }

        // Send notification
        GlobalFunction::insertUserNotification(
            Constants::notify_team_invite,
            $user->id,
            $memberId,
            $access->id
        );

        $access->load(['member:id,username,fullname,profile_photo,is_verify']);

        return [
            'status' => true,
            'message' => 'Team member invited',
            'data' => $access,
        ];
    }

    // ─── Respond to Team Invite ──────────────────────────────────

    public function respondToTeamInvite(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));

        $access = SharedAccess::where('id', $request->access_id)
            ->where('member_user_id', $user->id)
            ->where('status', SharedAccess::STATUS_PENDING)
            ->first();

        if (!$access) {
            return GlobalFunction::sendSimpleResponse(false, 'Invite not found');
        }

        $accept = $request->accept == true || $request->accept == 1;

        if ($accept) {
            $access->update(['status' => SharedAccess::STATUS_ACCEPTED]);

            // Notify account owner
            GlobalFunction::insertUserNotification(
                Constants::notify_team_accepted,
                $user->id,
                $access->account_user_id,
                $access->id
            );

            return GlobalFunction::sendSimpleResponse(true, 'Team invite accepted');
        } else {
            $access->update(['status' => SharedAccess::STATUS_DECLINED]);
            return GlobalFunction::sendSimpleResponse(true, 'Team invite declined');
        }
    }

    // ─── Fetch My Team Members (accounts I own) ──────────────────

    public function fetchMyTeamMembers(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));

        $members = SharedAccess::where('account_user_id', $user->id)
            ->whereIn('status', [SharedAccess::STATUS_PENDING, SharedAccess::STATUS_ACCEPTED])
            ->with(['member:id,username,fullname,profile_photo,is_verify'])
            ->orderByDesc('id')
            ->get();

        return [
            'status' => true,
            'message' => '',
            'data' => $members,
        ];
    }

    // ─── Fetch Managed Accounts (accounts I'm a member of) ──────

    public function fetchManagedAccounts(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));

        $accounts = SharedAccess::where('member_user_id', $user->id)
            ->where('status', SharedAccess::STATUS_ACCEPTED)
            ->with(['accountOwner:id,username,fullname,profile_photo,is_verify'])
            ->orderByDesc('id')
            ->get();

        return [
            'status' => true,
            'message' => '',
            'data' => $accounts,
        ];
    }

    // ─── Fetch Pending Team Invites ──────────────────────────────

    public function fetchTeamInvites(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));

        $invites = SharedAccess::where('member_user_id', $user->id)
            ->where('status', SharedAccess::STATUS_PENDING)
            ->with(['accountOwner:id,username,fullname,profile_photo,is_verify'])
            ->orderByDesc('id')
            ->get();

        return [
            'status' => true,
            'message' => '',
            'data' => $invites,
        ];
    }

    // ─── Update Team Member Role/Permissions ─────────────────────

    public function updateTeamMember(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));

        $access = SharedAccess::where('id', $request->access_id)
            ->where('account_user_id', $user->id)
            ->first();

        if (!$access) {
            return GlobalFunction::sendSimpleResponse(false, 'Team member not found');
        }

        $updateData = [];

        if ($request->has('role')) {
            $role = (int) $request->role;
            $updateData['role'] = $role;
            // Reset permissions to defaults for new role unless custom permissions provided
            if (!$request->has('permissions')) {
                $updateData['permissions'] = SharedAccess::defaultPermissions($role);
            }
        }

        if ($request->has('permissions')) {
            $updateData['permissions'] = $request->permissions;
        }

        $access->update($updateData);
        $access->load(['member:id,username,fullname,profile_photo,is_verify']);

        return [
            'status' => true,
            'message' => 'Team member updated',
            'data' => $access,
        ];
    }

    // ─── Remove Team Member ──────────────────────────────────────

    public function removeTeamMember(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));

        $access = SharedAccess::where('id', $request->access_id)
            ->where('account_user_id', $user->id)
            ->first();

        if (!$access) {
            return GlobalFunction::sendSimpleResponse(false, 'Team member not found');
        }

        $access->delete();

        return GlobalFunction::sendSimpleResponse(true, 'Team member removed');
    }

    // ─── Leave Team (member leaves voluntarily) ──────────────────

    public function leaveTeam(Request $request)
    {
        $user = GlobalFunction::getUserFromAuthToken($request->header('authtoken'));

        $access = SharedAccess::where('id', $request->access_id)
            ->where('member_user_id', $user->id)
            ->first();

        if (!$access) {
            return GlobalFunction::sendSimpleResponse(false, 'Team membership not found');
        }

        $access->delete();

        return GlobalFunction::sendSimpleResponse(true, 'Left team successfully');
    }

    // ─── Admin: List Shared Access ───────────────────────────────

    public function listSharedAccess_Admin(Request $request)
    {
        $totalData = SharedAccess::count();
        $totalFiltered = $totalData;
        $limit = $request->input('length');
        $start = $request->input('start');

        $query = SharedAccess::with([
            'accountOwner:id,username,fullname,profile_photo',
            'member:id,username,fullname,profile_photo',
        ]);

        if (!empty($request->input('search.value'))) {
            $search = $request->input('search.value');
            $query->whereHas('accountOwner', function ($q) use ($search) {
                $q->where('username', 'ilike', "%$search%");
            })->orWhereHas('member', function ($q) use ($search) {
                $q->where('username', 'ilike', "%$search%");
            });
            $totalFiltered = $query->count();
        }

        $records = $query->orderByDesc('id')->offset($start)->limit($limit)->get();

        $statusLabels = [0 => 'Pending', 1 => 'Accepted', 2 => 'Declined'];
        $statusClasses = [0 => 'warning', 1 => 'success', 2 => 'danger'];

        $data = [];
        foreach ($records as $i => $record) {
            $data[] = [
                $start + $i + 1,
                $record->accountOwner->username ?? '-',
                $record->member->username ?? '-',
                SharedAccess::roleLabel($record->role),
                '<span class="badge bg-' . ($statusClasses[$record->status] ?? 'secondary') . '">' . ($statusLabels[$record->status] ?? '-') . '</span>',
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
