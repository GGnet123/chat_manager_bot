<?php

namespace App\Jobs;

use App\Models\ClientAction;
use App\Services\Notification\NotificationDispatcher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendGroupNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 15;

    public function __construct(
        private int $actionId,
        private string $platform,
        private string $chatId,
    ) {}

    public function handle(NotificationDispatcher $dispatcher): void
    {
        $action = ClientAction::with('business', 'client')->find($this->actionId);

        if (!$action) {
            Log::warning('Action not found for notification', ['action_id' => $this->actionId]);
            return;
        }

        $result = $dispatcher->sendNotification($action, $this->platform, $this->chatId);

        if (!$result) {
            throw new \RuntimeException("Failed to send {$this->platform} notification to {$this->chatId}");
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('SendGroupNotificationJob failed permanently', [
            'error' => $exception->getMessage(),
            'action_id' => $this->actionId,
            'platform' => $this->platform,
            'chat_id' => $this->chatId,
        ]);
    }
}
