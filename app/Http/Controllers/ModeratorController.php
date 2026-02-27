<?php

namespace App\Http\Controllers;

use App\Models\BannedWord;
use App\Models\GlobalFunction;
use App\Models\ModerationLog;
use App\Models\Posts;
use App\Models\ReportPosts;
use App\Models\ReportUsers;
use App\Models\Story;
use App\Models\UserViolation;
use App\Models\Users;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;

class ModeratorController extends Controller
{
    // ─── Helper: check moderator role ────────────────────────────────
    private function requireModerator(Request $request): ?array
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if ($user->is_freez == 1) {
            return null;
        }
        if ($user->is_moderator != 1) {
            return null;
        }
        return ['user' => $user];
    }

    // ─── Existing endpoints (preserved) ──────────────────────────────

    public function moderator_unFreezeUser(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => "this user is freezed!"];
        }

        $rules = ['user_id' => 'required|exists:tbl_users,id'];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        if ($user->is_moderator != 1) {
            return GlobalFunction::sendSimpleResponse(false, 'you are not allowed to make this action!');
        }

        $dataUser = Users::find($request->user_id);
        $dataUser->is_freez = 0;
        $dataUser->ban_until = null;
        $dataUser->ban_reason = null;
        $dataUser->save();

        ModerationLog::log($user->id, 'unfreeze_user', 'user', $dataUser->id);

        return GlobalFunction::sendSimpleResponse(true, 'user un-freezed successfully');
    }

    public function moderator_freezeUser(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => "this user is freezed!"];
        }

        $rules = ['user_id' => 'required|exists:tbl_users,id'];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        if ($user->is_moderator != 1) {
            return GlobalFunction::sendSimpleResponse(false, 'you are not allowed to make this action!');
        }

        $dataUser = Users::find($request->user_id);
        if ($user->id == $dataUser->id) {
            return GlobalFunction::sendSimpleResponse(false, 'you can not freeze yourself!');
        }

        $dataUser->is_freez = 1;
        $dataUser->ban_reason = $request->reason ?? 'Moderator action';
        if ($request->has('ban_days') && $request->ban_days > 0) {
            $dataUser->ban_until = Carbon::now()->addDays((int) $request->ban_days);
        }
        $dataUser->save();

        ModerationLog::log($user->id, 'freeze_user', 'user', $dataUser->id, $request->reason);

        return GlobalFunction::sendSimpleResponse(true, 'user freezed successfully');
    }

    public function moderator_deleteStory(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => "this user is freezed!"];
        }

        $validator = Validator::make($request->all(), [
            'story_id' => 'required|exists:stories,id',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        if ($user->is_moderator != 1) {
            return GlobalFunction::sendSimpleResponse(false, 'you are not allowed to make this action!');
        }

        $story = Story::find($request->story_id);
        if ($story->content != null) {
            GlobalFunction::deleteFile($story->content);
        }
        if ($story->thumbnail != null) {
            GlobalFunction::deleteFile($story->thumbnail);
        }

        ModerationLog::log($user->id, 'delete_story', 'story', $story->id);

        $story->delete();

        return response()->json(['status' => true, 'message' => 'Story delete successfully']);
    }

    public function moderator_deletePost(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => "this user is freezed!"];
        }

        $rules = ['post_id' => 'required|exists:tbl_post,id'];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        if ($user->is_moderator != 1) {
            return GlobalFunction::sendSimpleResponse(false, 'you are not allowed to make this action!');
        }

        $post = Posts::find($request->post_id);

        ModerationLog::log($user->id, 'delete_post', 'post', $post->id, $request->reason ?? null);

        $post->delete();
        GlobalFunction::deleteAllPostData($post);

        return GlobalFunction::sendSimpleResponse(true, 'post deleted successfully!');
    }

    // ─── New Phase 17 endpoints ──────────────────────────────────────

    /**
     * Issue a warning / violation to a user.
     * Auto-escalation: 3 warnings → temp ban (7d), 5 → temp ban (30d), 7+ → permanent
     */
    public function issueViolation(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => "this user is freezed!"];
        }
        if ($user->is_moderator != 1) {
            return GlobalFunction::sendSimpleResponse(false, 'not authorized');
        }

        $rules = [
            'user_id' => 'required|exists:tbl_users,id',
            'severity' => 'required|integer|min:1|max:4',
            'reason' => 'required|string',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $targetUser = Users::find($request->user_id);
        $severity = (int) $request->severity;

        // Create violation record
        $violation = UserViolation::create([
            'user_id' => $targetUser->id,
            'moderator_id' => $user->id,
            'severity' => $severity,
            'reason' => $request->reason,
            'description' => $request->description ?? null,
            'reference_post_id' => $request->post_id ?? null,
            'reference_report_id' => $request->report_id ?? null,
            'action_taken' => 'warning',
        ]);

        // Increment violation count
        $targetUser->violation_count = ($targetUser->violation_count ?? 0) + 1;
        $totalViolations = $targetUser->violation_count;

        // Auto-escalation logic
        $banDays = null;
        if ($severity >= 4 || $totalViolations >= 7) {
            // Permanent ban
            $targetUser->is_freez = 1;
            $targetUser->ban_reason = 'Auto-escalation: ' . $request->reason;
            $violation->action_taken = 'permanent_ban';
        } elseif ($totalViolations >= 5 || $severity == 3) {
            // 30-day temp ban
            $banDays = 30;
            $targetUser->is_freez = 1;
            $targetUser->ban_until = Carbon::now()->addDays(30);
            $targetUser->ban_reason = 'Temp ban (30d): ' . $request->reason;
            $violation->action_taken = 'temp_ban';
            $violation->ban_days = 30;
        } elseif ($totalViolations >= 3) {
            // 7-day temp ban
            $banDays = 7;
            $targetUser->is_freez = 1;
            $targetUser->ban_until = Carbon::now()->addDays(7);
            $targetUser->ban_reason = 'Temp ban (7d): ' . $request->reason;
            $violation->action_taken = 'temp_ban';
            $violation->ban_days = 7;
        }

        $violation->save();
        $targetUser->save();

        ModerationLog::log($user->id, 'issue_violation', 'user', $targetUser->id,
            "Severity: {$severity}, Action: {$violation->action_taken}, Reason: {$request->reason}");

        return GlobalFunction::sendDataResponse(true, 'violation issued', [
            'violation' => $violation,
            'total_violations' => $totalViolations,
            'action_taken' => $violation->action_taken,
            'ban_days' => $banDays,
        ]);
    }

    /**
     * Fetch pending reports for in-app moderator panel
     */
    public function fetchPendingReports(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => "this user is freezed!"];
        }
        if ($user->is_moderator != 1) {
            return GlobalFunction::sendSimpleResponse(false, 'not authorized');
        }

        $type = $request->type ?? 'post'; // post or user
        $limit = $request->limit ?? 20;
        $lastItemId = $request->last_item_id;

        if ($type === 'user') {
            $query = ReportUsers::where('status', 0)
                ->with(['by_user:id,username,fullname,profile_photo', 'user:id,username,fullname,profile_photo'])
                ->orderBy('id', 'DESC')
                ->limit($limit);
            if ($lastItemId) {
                $query->where('id', '<', $lastItemId);
            }
            $reports = $query->get();
        } else {
            $query = ReportPosts::where('status', 0)
                ->with(['by_user:id,username,fullname,profile_photo', 'post'])
                ->orderBy('id', 'DESC')
                ->limit($limit);
            if ($lastItemId) {
                $query->where('id', '<', $lastItemId);
            }
            $reports = $query->get();
        }

        return GlobalFunction::sendDataResponse(true, 'reports fetched', $reports);
    }

    /**
     * Resolve a report (accept or dismiss) from in-app moderator panel
     */
    public function resolveReport(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => "this user is freezed!"];
        }
        if ($user->is_moderator != 1) {
            return GlobalFunction::sendSimpleResponse(false, 'not authorized');
        }

        $rules = [
            'report_id' => 'required',
            'type' => 'required|in:post,user',
            'action' => 'required|in:accept,dismiss',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $action = $request->action;
        $type = $request->type;

        if ($type === 'post') {
            $report = ReportPosts::find($request->report_id);
            if (!$report) {
                return GlobalFunction::sendSimpleResponse(false, 'report not found');
            }

            if ($action === 'accept') {
                // Delete the post
                $post = Posts::find($report->post_id);
                if ($post) {
                    $post->delete();
                    GlobalFunction::deleteAllPostData($post);
                }
                $report->status = 2; // resolved
            } else {
                $report->status = 3; // dismissed
            }
            $report->reviewed_by = $user->id;
            $report->save();

            ModerationLog::log($user->id, $action === 'accept' ? 'accept_post_report' : 'reject_post_report',
                'report', $report->id);
        } else {
            $report = ReportUsers::find($request->report_id);
            if (!$report) {
                return GlobalFunction::sendSimpleResponse(false, 'report not found');
            }

            if ($action === 'accept') {
                $targetUser = Users::find($report->user_id);
                if ($targetUser) {
                    $targetUser->is_freez = 1;
                    $targetUser->ban_reason = 'Report accepted: ' . $report->reason;
                    $targetUser->save();
                }
                $report->status = 2;
            } else {
                $report->status = 3;
            }
            $report->reviewed_by = $user->id;
            $report->save();

            ModerationLog::log($user->id, $action === 'accept' ? 'accept_user_report' : 'reject_user_report',
                'report', $report->id);
        }

        return GlobalFunction::sendSimpleResponse(true, 'report ' . ($action === 'accept' ? 'accepted' : 'dismissed'));
    }

    /**
     * Fetch user violation history
     */
    public function fetchUserViolations(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => "this user is freezed!"];
        }
        if ($user->is_moderator != 1) {
            return GlobalFunction::sendSimpleResponse(false, 'not authorized');
        }

        $rules = ['user_id' => 'required|exists:tbl_users,id'];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
        }

        $violations = UserViolation::where('user_id', $request->user_id)
            ->orderBy('id', 'DESC')
            ->limit($request->limit ?? 50)
            ->get();

        $targetUser = Users::find($request->user_id);

        return GlobalFunction::sendDataResponse(true, 'violations fetched', [
            'violations' => $violations,
            'violation_count' => $targetUser->violation_count ?? 0,
            'is_banned' => $targetUser->is_freez == 1,
            'ban_until' => $targetUser->ban_until,
            'ban_reason' => $targetUser->ban_reason,
        ]);
    }

    /**
     * Fetch moderator's own action log
     */
    public function fetchModerationLog(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => "this user is freezed!"];
        }
        if ($user->is_moderator != 1) {
            return GlobalFunction::sendSimpleResponse(false, 'not authorized');
        }

        $limit = $request->limit ?? 30;
        $lastItemId = $request->last_item_id;

        $query = ModerationLog::where('moderator_id', $user->id)
            ->orderBy('id', 'DESC')
            ->limit($limit);
        if ($lastItemId) {
            $query->where('id', '<', $lastItemId);
        }

        $logs = $query->get();

        return GlobalFunction::sendDataResponse(true, 'moderation log fetched', $logs);
    }

    /**
     * Check text content against banned words
     */
    public function checkBannedWords(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => "this user is freezed!"];
        }

        $text = $request->text ?? '';
        if (empty($text)) {
            return GlobalFunction::sendDataResponse(true, 'no text to check', ['violations' => []]);
        }

        $violations = BannedWord::checkText($text);

        return GlobalFunction::sendDataResponse(true, 'check complete', [
            'has_violations' => count($violations) > 0,
            'violations' => $violations,
        ]);
    }

    /**
     * Get moderation summary stats for moderator dashboard
     */
    public function fetchModerationStats(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);
        if ($user->is_freez == 1) {
            return ['status' => false, 'message' => "this user is freezed!"];
        }
        if ($user->is_moderator != 1) {
            return GlobalFunction::sendSimpleResponse(false, 'not authorized');
        }

        $pendingPostReports = ReportPosts::where('status', 0)->count();
        $pendingUserReports = ReportUsers::where('status', 0)->count();
        $totalActions = ModerationLog::where('moderator_id', $user->id)->count();
        $actionsToday = ModerationLog::where('moderator_id', $user->id)
            ->where('created_at', '>=', Carbon::today())
            ->count();
        $recentViolations = UserViolation::where('created_at', '>=', Carbon::now()->subDays(7))->count();

        return GlobalFunction::sendDataResponse(true, 'stats fetched', [
            'pending_post_reports' => $pendingPostReports,
            'pending_user_reports' => $pendingUserReports,
            'total_pending' => $pendingPostReports + $pendingUserReports,
            'my_total_actions' => $totalActions,
            'my_actions_today' => $actionsToday,
            'recent_violations_7d' => $recentViolations,
        ]);
    }
}
