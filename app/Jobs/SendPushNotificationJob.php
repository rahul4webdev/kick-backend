<?php

namespace App\Jobs;

use App\Models\GlobalFunction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendPushNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 10;

    private array $payload;

    public function __construct(array $payload)
    {
        $this->payload = $payload;
        $this->onQueue('notifications');
    }

    public function handle(): void
    {
        GlobalFunction::sendPushNotification($this->payload);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('SendPushNotificationJob failed', [
            'error' => $exception->getMessage(),
        ]);
    }
}
