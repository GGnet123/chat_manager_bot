<?php

namespace App\Jobs;

use App\Models\Business;
use App\Services\Messaging\Telegram\TelegramService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SetupTelegramWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 10;

    public function __construct(
        public int $businessId
    ) {}

    public function handle(TelegramService $telegramService): void
    {
        $business = Business::find($this->businessId);

        if (!$business) {
            Log::warning('SetupTelegramWebhookJob: Business not found', ['business_id' => $this->businessId]);
            return;
        }

        if (empty($business->telegram_bot_token)) {
            Log::info('SetupTelegramWebhookJob: No bot token configured', ['business_id' => $this->businessId]);
            return;
        }

        if (empty($business->telegram_webhook_id) || empty($business->telegram_webhook_secret)) {
            Log::warning('SetupTelegramWebhookJob: Missing webhook ID or secret', ['business_id' => $this->businessId]);
            return;
        }

        // Build webhook URL using the webhook_id
        $webhookUrl = $this->buildWebhookUrl($business->telegram_webhook_id);

        Log::info('SetupTelegramWebhookJob: Setting webhook', [
            'business_id' => $this->businessId,
            'business_name' => $business->name,
            'webhook_url' => $webhookUrl,
        ]);

        $success = $telegramService->setWebhook(
            $business->telegram_bot_token,
            $webhookUrl,
            $business->telegram_webhook_secret
        );

        if ($success) {
            Log::info('SetupTelegramWebhookJob: Webhook set successfully', [
                'business_id' => $this->businessId,
                'business_name' => $business->name,
            ]);
        } else {
            Log::error('SetupTelegramWebhookJob: Failed to set webhook', [
                'business_id' => $this->businessId,
                'business_name' => $business->name,
            ]);

            // Throw exception to trigger retry
            throw new \RuntimeException("Failed to set Telegram webhook for business {$this->businessId}");
        }
    }

    private function buildWebhookUrl(string $webhookId): string
    {
        $baseUrl = config('app.url');

        // Ensure HTTPS for Telegram webhooks (required by Telegram)
        if (str_starts_with($baseUrl, 'http://')) {
            $baseUrl = str_replace('http://', 'https://', $baseUrl);
        }

        return rtrim($baseUrl, '/') . '/api/webhook/telegram/' . $webhookId;
    }
}
