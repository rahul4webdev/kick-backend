<?php

namespace App\Jobs;

use App\Models\GlobalFunction;
use App\Models\Users;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DeleteUserAccountJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 300;

    private int $userId;

    public function __construct(int $userId)
    {
        $this->userId = $userId;
        $this->onQueue('default');
    }

    public function handle(): void
    {
        $user = Users::find($this->userId);
        if ($user) {
            GlobalFunction::deleteUserAccount($user);
            $user->delete();
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('DeleteUserAccountJob failed', [
            'user_id' => $this->userId,
            'error' => $exception->getMessage(),
        ]);
    }
}
