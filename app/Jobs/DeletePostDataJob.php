<?php

namespace App\Jobs;

use App\Models\GlobalFunction;
use App\Models\Posts;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DeletePostDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 120;

    private int $postId;
    private int $userId;
    private ?string $hashtags;
    private ?int $soundId;

    public function __construct(int $postId, int $userId, ?string $hashtags, ?int $soundId)
    {
        $this->postId = $postId;
        $this->userId = $userId;
        $this->hashtags = $hashtags;
        $this->soundId = $soundId;
        $this->onQueue('default');
    }

    public function handle(): void
    {
        $post = Posts::withTrashed()->find($this->postId);
        if (!$post) {
            $post = new Posts();
            $post->id = $this->postId;
            $post->user_id = $this->userId;
            $post->hashtags = $this->hashtags;
            $post->sound_id = $this->soundId;
        }
        GlobalFunction::deleteAllPostData($post);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('DeletePostDataJob failed', [
            'post_id' => $this->postId,
            'error' => $exception->getMessage(),
        ]);
    }
}
