<?php

namespace App\Services\Messaging\Telegram;

use App\DataTransferObjects\OutgoingMessageDTO;
use App\Models\Business;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramMessageSender
{
    public function send(Business $business, OutgoingMessageDTO $message): bool
    {
        $url = $this->buildApiUrl($business->telegram_bot_token, 'sendMessage');

        try {
            $payload = $this->buildPayload($message);

            $response = Http::post($url, $payload);

            if ($response->successful() && $response->json('ok')) {
                Log::info('Telegram message sent successfully', [
                    'chat_id' => $message->recipientId,
                    'message_id' => $response->json('result.message_id'),
                ]);
                return true;
            }

            Log::error('Telegram API error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return false;
        } catch (\Throwable $e) {
            Log::error('Failed to send Telegram message', [
                'error' => $e->getMessage(),
                'chat_id' => $message->recipientId,
            ]);
            return false;
        }
    }

    public function sendToGroup(string $botToken, string $chatId, string $text): bool
    {
        $url = $this->buildApiUrl($botToken, 'sendMessage');

        try {
            $response = Http::post($url, [
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => 'HTML',
            ]);

            return $response->successful() && $response->json('ok');
        } catch (\Throwable $e) {
            Log::error('Failed to send Telegram group message', [
                'error' => $e->getMessage(),
                'chat_id' => $chatId,
            ]);
            return false;
        }
    }

    private function buildApiUrl(string $botToken, string $method): string
    {
        return "https://api.telegram.org/bot{$botToken}/{$method}";
    }

    private function buildPayload(OutgoingMessageDTO $message): array
    {
        $payload = [
            'chat_id' => $message->recipientId,
            'text' => $message->content,
            'parse_mode' => 'HTML',
        ];

        if ($message->replyToMessageId) {
            $payload['reply_to_message_id'] = $message->replyToMessageId;
        }

        return $payload;
    }
}
