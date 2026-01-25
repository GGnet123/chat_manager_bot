<?php

namespace App\Services\Notification;

use App\Models\Business;
use App\Services\Messaging\Telegram\TelegramMessageSender;
use Illuminate\Support\Facades\Log;

class TelegramGroupNotifier
{
    public function __construct(
        private TelegramMessageSender $sender,
    ) {}

    public function send(Business $business, string $chatId, string $message): bool
    {
        if (!$business->telegram_bot_token) {
            Log::warning('Business has no Telegram bot token configured', [
                'business_id' => $business->id,
            ]);
            return false;
        }

        $result = $this->sender->sendToGroup(
            $business->telegram_bot_token,
            $chatId,
            $this->formatForTelegram($message)
        );

        if ($result) {
            Log::info('Telegram group notification sent', [
                'chat_id' => $chatId,
                'business_id' => $business->id,
            ]);
        } else {
            Log::error('Telegram group notification failed', [
                'chat_id' => $chatId,
                'business_id' => $business->id,
            ]);
        }

        return $result;
    }

    private function formatForTelegram(string $message): string
    {
        // Convert basic formatting to HTML for Telegram
        // Telegram supports HTML: <b>, <i>, <code>, <pre>, <a>
        return $message;
    }
}
