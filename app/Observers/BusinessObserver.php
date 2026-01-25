<?php

namespace App\Observers;

use App\Models\Business;
use App\Jobs\SetupTelegramWebhookJob;
use Illuminate\Support\Str;

class BusinessObserver
{
    /**
     * Handle the Business "created" event.
     */
    public function created(Business $business): void
    {
        $this->setupTelegramWebhookIfNeeded($business, true);
    }

    /**
     * Handle the Business "updated" event.
     */
    public function updated(Business $business): void
    {
        // Check if telegram_bot_token was changed
        if ($business->wasChanged('telegram_bot_token')) {
            $this->setupTelegramWebhookIfNeeded($business, true);
        }
    }

    /**
     * Setup Telegram webhook if bot token is present.
     */
    private function setupTelegramWebhookIfNeeded(Business $business, bool $tokenChanged): void
    {
        // If token was removed, clear webhook fields
        if (empty($business->telegram_bot_token)) {
            $business->updateQuietly([
                'telegram_webhook_id' => null,
                'telegram_webhook_secret' => null,
            ]);
            return;
        }

        // Generate new webhook ID and secret if token changed or they don't exist
        if ($tokenChanged || empty($business->telegram_webhook_id)) {
            $webhookId = Str::random(32);
            $webhookSecret = Str::random(64);

            $business->updateQuietly([
                'telegram_webhook_id' => $webhookId,
                'telegram_webhook_secret' => $webhookSecret,
            ]);

            // Refresh to get the updated values
            $business->refresh();
        }

        // Dispatch job to set webhook with Telegram API
        SetupTelegramWebhookJob::dispatch($business->id);
    }
}
