<?php

namespace App\Jobs;

use App\Models\DataDownloadRequest;
use App\Models\Users;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use ZipArchive;

class ExportUserDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 600;

    private int $requestId;
    private int $userId;

    public function __construct(int $requestId, int $userId)
    {
        $this->requestId = $requestId;
        $this->userId = $userId;
        $this->onQueue('default');
    }

    public function handle(): void
    {
        $downloadRequest = DataDownloadRequest::find($this->requestId);
        if (!$downloadRequest) return;

        $downloadRequest->status = DataDownloadRequest::STATUS_PROCESSING;
        $downloadRequest->save();

        try {
            $user = Users::find($this->userId);
            if (!$user) {
                $downloadRequest->status = DataDownloadRequest::STATUS_FAILED;
                $downloadRequest->save();
                return;
            }

            $data = [];

            // 1. Profile data
            $data['profile'] = [
                'id' => $user->id,
                'username' => $user->username,
                'fullname' => $user->fullname,
                'bio' => $user->bio,
                'pronouns' => $user->pronouns,
                'user_email' => $user->user_email,
                'user_mobile_no' => $user->user_mobile_no,
                'identity' => $user->identity,
                'login_method' => $user->login_method,
                'account_type' => $user->account_type,
                'is_private' => $user->is_private,
                'created_at' => $user->created_at,
            ];

            // 2. Posts
            $posts = DB::table('tbl_post')
                ->where('user_id', $this->userId)
                ->select('id', 'description', 'post_type', 'content_type', 'video', 'thumbnail', 'views_count', 'created_at')
                ->orderBy('created_at', 'desc')
                ->get();
            $data['posts'] = $posts->toArray();

            // 3. Comments
            $comments = DB::table('tbl_comment')
                ->where('user_id', $this->userId)
                ->select('id', 'post_id', 'comment', 'created_at')
                ->orderBy('created_at', 'desc')
                ->get();
            $data['comments'] = $comments->toArray();

            // 4. Likes
            $likes = DB::table('tbl_favourite_post')
                ->where('user_id', $this->userId)
                ->select('post_id', 'created_at')
                ->orderBy('created_at', 'desc')
                ->get();
            $data['likes'] = $likes->toArray();

            // 5. Saved posts
            $saves = DB::table('tbl_save_post')
                ->where('user_id', $this->userId)
                ->select('post_id', 'created_at')
                ->orderBy('created_at', 'desc')
                ->get();
            $data['saved_posts'] = $saves->toArray();

            // 6. Followers
            $followers = DB::table('tbl_followers')
                ->where('to_user_id', $this->userId)
                ->select('from_user_id', 'created_at')
                ->get();
            $data['followers'] = $followers->toArray();

            // 7. Following
            $following = DB::table('tbl_followers')
                ->where('from_user_id', $this->userId)
                ->select('to_user_id', 'created_at')
                ->get();
            $data['following'] = $following->toArray();

            // 8. Blocked users
            $blocked = DB::table('tbl_user_blocks')
                ->where('user_id', $this->userId)
                ->select('blocked_user_id', 'created_at')
                ->get();
            $data['blocked_users'] = $blocked->toArray();

            // 9. Login sessions
            $sessions = DB::table('tbl_login_sessions')
                ->where('user_id', $this->userId)
                ->select('device', 'device_brand', 'device_model', 'device_os', 'ip_address', 'login_method', 'logged_in_at')
                ->orderBy('logged_in_at', 'desc')
                ->get();
            $data['login_sessions'] = $sessions->toArray();

            // 10. Coin transactions
            $transactions = DB::table('tbl_coin_transactions')
                ->where('user_id', $this->userId)
                ->select('amount', 'type', 'description', 'created_at')
                ->orderBy('created_at', 'desc')
                ->limit(500)
                ->get();
            $data['coin_transactions'] = $transactions->toArray();

            // Create JSON file
            $jsonContent = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

            // Create ZIP
            $fileName = 'user_data_' . $this->userId . '_' . time() . '.zip';
            $relativePath = 'uploads/exports/' . $fileName;
            $fullDir = storage_path('app/public/exports');
            if (!is_dir($fullDir)) {
                mkdir($fullDir, 0755, true);
            }
            $zipPath = $fullDir . '/' . $fileName;

            $zip = new ZipArchive();
            if ($zip->open($zipPath, ZipArchive::CREATE) === true) {
                $zip->addFromString('your_data.json', $jsonContent);
                $zip->close();
            } else {
                throw new \Exception('Failed to create ZIP file');
            }

            $fileSize = filesize($zipPath);

            $downloadRequest->status = DataDownloadRequest::STATUS_READY;
            $downloadRequest->file_path = $relativePath;
            $downloadRequest->file_size = $fileSize;
            $downloadRequest->ready_at = now();
            $downloadRequest->expires_at = now()->addDays(7);
            $downloadRequest->save();

        } catch (\Throwable $e) {
            Log::error('ExportUserDataJob failed', [
                'request_id' => $this->requestId,
                'user_id' => $this->userId,
                'error' => $e->getMessage(),
            ]);
            $downloadRequest->status = DataDownloadRequest::STATUS_FAILED;
            $downloadRequest->save();
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ExportUserDataJob failed (queue)', [
            'request_id' => $this->requestId,
            'user_id' => $this->userId,
            'error' => $exception->getMessage(),
        ]);

        $downloadRequest = DataDownloadRequest::find($this->requestId);
        if ($downloadRequest) {
            $downloadRequest->status = DataDownloadRequest::STATUS_FAILED;
            $downloadRequest->save();
        }
    }
}
