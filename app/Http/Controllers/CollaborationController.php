<?php

namespace App\Http\Controllers;

use App\Models\Constants;
use App\Models\GlobalFunction;
use App\Models\PostCollaborator;
use App\Models\Posts;
use App\Models\Users;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CollaborationController extends Controller
{
    /**
     * Invite a user to collaborate on a post.
     * Params: post_id, user_id
     */
    public function inviteCollaborator(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);

        $rules = [
            'post_id' => 'required|exists:tbl_post,id',
            'user_id' => 'required|exists:tbl_users,id',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return GlobalFunction::sendSimpleResponse(false, $validator->errors()->first());
        }

        $post = Posts::find($request->post_id);

        // Only the post owner can invite collaborators
        if ($post->user_id != $user->id) {
            return GlobalFunction::sendSimpleResponse(false, 'Only the post owner can invite collaborators');
        }

        // Can't invite yourself
        if ($request->user_id == $user->id) {
            return GlobalFunction::sendSimpleResponse(false, 'You cannot invite yourself');
        }

        // Max 4 collaborators per post
        $existingCount = PostCollaborator::where('post_id', $post->id)->count();
        if ($existingCount >= 4) {
            return GlobalFunction::sendSimpleResponse(false, 'Maximum 4 collaborators per post');
        }

        // Check if already invited
        $existing = PostCollaborator::where('post_id', $post->id)
            ->where('user_id', $request->user_id)
            ->first();

        if ($existing) {
            return GlobalFunction::sendSimpleResponse(false, 'User already invited');
        }

        $collab = PostCollaborator::create([
            'post_id' => $post->id,
            'user_id' => $request->user_id,
            'invited_by' => $user->id,
            'status' => PostCollaborator::STATUS_PENDING,
            'role' => $request->role ?? 'collaborator',
        ]);

        // Send notification to the invited user
        GlobalFunction::insertUserNotification(
            Constants::notify_collab_invite,
            $user->id,
            $request->user_id,
            $post->id
        );

        // Push notification
        $toUser = Users::find($request->user_id);
        if ($toUser) {
            GlobalFunction::initiatePushNotification(
                true,
                true,
                $toUser,
                $user->username . ' invited you to collaborate',
                'Tap to view the collaboration request',
                ['type' => 'collab_invite', 'post_id' => $post->id]
            );
        }

        return GlobalFunction::sendDataResponse(true, 'Collaboration invite sent', $collab);
    }

    /**
     * Respond to a collaboration invite.
     * Params: collaboration_id, action (accept/decline)
     */
    public function respondToInvite(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);

        $rules = [
            'collaboration_id' => 'required',
            'action' => 'required|in:accept,decline',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return GlobalFunction::sendSimpleResponse(false, $validator->errors()->first());
        }

        $collab = PostCollaborator::where('id', $request->collaboration_id)
            ->where('user_id', $user->id)
            ->where('status', PostCollaborator::STATUS_PENDING)
            ->first();

        if (!$collab) {
            return GlobalFunction::sendSimpleResponse(false, 'Collaboration invite not found');
        }

        if ($request->action === 'accept') {
            $collab->status = PostCollaborator::STATUS_ACCEPTED;
            $collab->save();

            // Notify the post owner
            GlobalFunction::insertUserNotification(
                Constants::notify_collab_accepted,
                $user->id,
                $collab->invited_by,
                $collab->post_id
            );

            // Check if all collaborators accepted → mark post as collaborative
            $this->checkAndMarkCollaborative($collab->post_id);

            return GlobalFunction::sendSimpleResponse(true, 'Collaboration accepted');
        } else {
            $collab->status = PostCollaborator::STATUS_DECLINED;
            $collab->save();
            return GlobalFunction::sendSimpleResponse(true, 'Collaboration declined');
        }
    }

    /**
     * Fetch pending collaboration invites for the current user.
     */
    public function fetchPendingInvites(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);

        $invites = PostCollaborator::with(['post', 'inviter' => function ($q) {
                $q->select(explode(',', Constants::userPublicFields));
            }])
            ->where('user_id', $user->id)
            ->where('status', PostCollaborator::STATUS_PENDING)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($collab) {
                return [
                    'id' => $collab->id,
                    'post_id' => $collab->post_id,
                    'post_thumbnail' => $collab->post?->thumbnail,
                    'post_description' => $collab->post?->description,
                    'inviter' => $collab->inviter,
                    'created_at' => $collab->created_at?->toISOString(),
                ];
            });

        return GlobalFunction::sendDataResponse(true, 'Pending invites fetched', $invites);
    }

    /**
     * Fetch collaborators for a post.
     * Params: post_id
     */
    public function fetchPostCollaborators(Request $request)
    {
        $token = $request->header('authtoken');
        GlobalFunction::getUserFromAuthToken($token);

        $collaborators = PostCollaborator::with(['user' => function ($q) {
                $q->select(explode(',', Constants::userPublicFields));
            }])
            ->where('post_id', $request->post_id)
            ->where('status', PostCollaborator::STATUS_ACCEPTED)
            ->get()
            ->map(function ($collab) {
                return [
                    'id' => $collab->id,
                    'user' => $collab->user,
                    'status' => $collab->status,
                ];
            });

        return GlobalFunction::sendDataResponse(true, 'Collaborators fetched', $collaborators);
    }

    /**
     * Remove a collaborator from a post.
     * Params: collaboration_id
     */
    public function removeCollaborator(Request $request)
    {
        $token = $request->header('authtoken');
        $user = GlobalFunction::getUserFromAuthToken($token);

        $collab = PostCollaborator::find($request->collaboration_id);
        if (!$collab) {
            return GlobalFunction::sendSimpleResponse(false, 'Collaboration not found');
        }

        // Only the post owner or the collaborator themselves can remove
        $post = Posts::find($collab->post_id);
        if ($post->user_id != $user->id && $collab->user_id != $user->id) {
            return GlobalFunction::sendSimpleResponse(false, 'Unauthorized');
        }

        $collab->delete();

        // Re-check collaborative status
        $remaining = PostCollaborator::where('post_id', $collab->post_id)
            ->where('status', PostCollaborator::STATUS_ACCEPTED)
            ->count();
        if ($remaining === 0) {
            Posts::where('id', $collab->post_id)->update(['is_collaborative' => false]);
        }

        return GlobalFunction::sendSimpleResponse(true, 'Collaborator removed');
    }

    /**
     * Check if all collaborators have accepted and mark the post as collaborative.
     */
    private function checkAndMarkCollaborative(int $postId): void
    {
        $pending = PostCollaborator::where('post_id', $postId)
            ->where('status', PostCollaborator::STATUS_PENDING)
            ->count();

        $accepted = PostCollaborator::where('post_id', $postId)
            ->where('status', PostCollaborator::STATUS_ACCEPTED)
            ->count();

        // Mark as collaborative if at least one collaborator accepted
        if ($accepted > 0) {
            Posts::where('id', $postId)->update(['is_collaborative' => true]);
        }

        // If all collaborators have responded (none pending) and at least one accepted,
        // distribute equal credit shares
        if ($pending === 0 && $accepted > 0) {
            $share = round(100 / ($accepted + 1), 2); // +1 for the original creator
            PostCollaborator::where('post_id', $postId)
                ->where('status', PostCollaborator::STATUS_ACCEPTED)
                ->update(['credit_share' => $share]);
        }
    }
}
