<?php

namespace App\Jobs;

use App\Models\CommentReplies;
use App\Models\Constants;
use App\Models\GlobalFunction;
use App\Models\PostComments;
use App\Models\Users;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessUserNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 5;

    private int $type;
    private int $fromUserId;
    private int $toUserId;
    private int $dataId;

    public function __construct(int $type, int $fromUserId, int $toUserId, int $dataId)
    {
        $this->type = $type;
        $this->fromUserId = $fromUserId;
        $this->toUserId = $toUserId;
        $this->dataId = $dataId;
        $this->onQueue('notifications');
    }

    public function handle(): void
    {
        // Step 1: Insert notification record in DB
        $notification = GlobalFunction::insertUserNotification(
            $this->type,
            $this->fromUserId,
            $this->toUserId,
            $this->dataId
        );

        // Step 2: Send push notification
        // Skip if self-notification (insertUserNotification returns null for self)
        if ($notification === null) {
            return;
        }

        $toUser = Users::find($this->toUserId);
        if (!$toUser || empty($toUser->device_token)) {
            return;
        }

        if (!$this->shouldSendPush($toUser)) {
            return;
        }

        // Quiet mode: skip push notification if enabled and within active period
        if ($toUser->quiet_mode_enabled) {
            if ($toUser->quiet_mode_until === null || now()->lt($toUser->quiet_mode_until)) {
                return; // Notification still saved in DB, just no push
            } else {
                // Quiet mode expired, auto-disable it
                $toUser->quiet_mode_enabled = false;
                $toUser->quiet_mode_until = null;
                $toUser->save();
            }
        }

        $fromUser = Users::find($this->fromUserId);
        $title = $fromUser ? ($fromUser->username ?? $fromUser->fullname ?? 'Someone') : 'Kick';
        $body = $this->getNotificationBody();
        $notificationData = $this->buildNotificationData();

        if ($notificationData === null) {
            return;
        }

        GlobalFunction::initiatePushNotification(
            true,
            true,
            $toUser,
            $title,
            $body,
            $notificationData
        );
    }

    private function shouldSendPush($toUser): bool
    {
        return match ($this->type) {
            Constants::notify_like_post => $toUser->notify_post_like == Constants::isNotifyYes,
            Constants::notify_comment_post,
            Constants::notify_reply_comment,
            Constants::notify_creator_liked_comment => $toUser->notify_post_comment == Constants::isNotifyYes,
            Constants::notify_mention_post,
            Constants::notify_mention_comment,
            Constants::notify_mention_reply => $toUser->notify_mention == Constants::isNotifyYes,
            Constants::notify_follow_user,
            Constants::notify_follow_request => $toUser->notify_follow == Constants::isNotifyYes,
            Constants::notify_gift_user => $toUser->notify_gift_received == Constants::isNotifyYes,
            Constants::notify_monetization_status => true,
            default => false,
        };
    }

    private function getNotificationBody(): string
    {
        return match ($this->type) {
            Constants::notify_like_post => 'liked your post',
            Constants::notify_comment_post => 'commented on your post',
            Constants::notify_mention_post => 'mentioned you in a post',
            Constants::notify_mention_comment => 'mentioned you in a comment',
            Constants::notify_follow_user => 'started following you',
            Constants::notify_gift_user => 'sent you a gift',
            Constants::notify_reply_comment => 'replied to your comment',
            Constants::notify_mention_reply => 'mentioned you in a reply',
            Constants::notify_follow_request => 'sent you a follow request',
            Constants::notify_monetization_status => 'Your monetization status has been updated',
            Constants::notify_creator_liked_comment => 'loved your comment',
            Constants::notify_tip_received => 'sent you a tip',
            Constants::notify_repost => 'reposted your post',
            Constants::notify_new_subscriber => 'subscribed to you',
            Constants::notify_collab_invite => 'invited you to collaborate',
            Constants::notify_collab_accepted => 'accepted your collaboration invite',
            Constants::notify_new_exclusive_content => 'posted exclusive content',
            default => 'You have a new notification',
        };
    }

    private function buildNotificationData(): ?array
    {
        switch ($this->type) {
            case Constants::notify_like_post:
                return [
                    'type' => 'post',
                    'notification_data' => json_encode(['id' => $this->dataId]),
                ];

            case Constants::notify_comment_post:
                $comment = PostComments::find($this->dataId);
                if (!$comment) return null;
                return [
                    'type' => 'post',
                    'notification_data' => json_encode([
                        'id' => $comment->post_id,
                        'comment_id' => $this->dataId,
                    ]),
                ];

            case Constants::notify_mention_post:
                return [
                    'type' => 'post',
                    'notification_data' => json_encode(['id' => $this->dataId]),
                ];

            case Constants::notify_mention_comment:
                $comment = PostComments::find($this->dataId);
                if (!$comment) return null;
                return [
                    'type' => 'post',
                    'notification_data' => json_encode([
                        'id' => $comment->post_id,
                        'comment_id' => $this->dataId,
                    ]),
                ];

            case Constants::notify_follow_user:
            case Constants::notify_gift_user:
            case Constants::notify_follow_request:
                return [
                    'type' => 'user',
                    'notification_data' => json_encode(['id' => $this->fromUserId]),
                ];

            case Constants::notify_reply_comment:
            case Constants::notify_mention_reply:
                $reply = CommentReplies::find($this->dataId);
                if (!$reply) return null;
                $comment = PostComments::find($reply->comment_id);
                if (!$comment) return null;
                return [
                    'type' => 'post',
                    'notification_data' => json_encode([
                        'id' => $comment->post_id,
                        'comment_id' => $reply->comment_id,
                        'reply_comment_id' => $this->dataId,
                    ]),
                ];

            case Constants::notify_creator_liked_comment:
                $comment = PostComments::find($this->dataId);
                if (!$comment) return null;
                return [
                    'type' => 'post',
                    'notification_data' => json_encode([
                        'id' => $comment->post_id,
                        'comment_id' => $this->dataId,
                    ]),
                ];

            case Constants::notify_tip_received:
            case Constants::notify_repost:
            case Constants::notify_new_subscriber:
            case Constants::notify_collab_invite:
            case Constants::notify_collab_accepted:
            case Constants::notify_new_exclusive_content:
                return [
                    'type' => 'user',
                    'notification_data' => json_encode(['id' => $this->fromUserId]),
                ];

            case Constants::notify_monetization_status:
                return [
                    'type' => 'user',
                    'notification_data' => json_encode(['id' => $this->toUserId]),
                ];

            default:
                return null;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessUserNotificationJob failed', [
            'type' => $this->type,
            'from_user' => $this->fromUserId,
            'to_user' => $this->toUserId,
            'error' => $exception->getMessage(),
        ]);
    }
}
