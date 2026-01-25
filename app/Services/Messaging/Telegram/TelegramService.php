<?php

namespace App\Services\Messaging\Telegram;

use App\Contracts\Messaging\MessengerInterface;
use App\DataTransferObjects\IncomingMessageDTO;
use App\DataTransferObjects\OutgoingMessageDTO;
use App\Models\Business;
use Illuminate\Support\Facades\Log;

class TelegramService implements MessengerInterface
{
    public function __construct(
        private TelegramMessageParser $parser,
        private TelegramMessageSender $sender,
    ) {}

    public function parseIncomingMessage(array $payload): ?IncomingMessageDTO
    {
        return $this->parser->parse($payload);
    }

    public function sendMessage(Business $business, OutgoingMessageDTO $message): bool
    {
        return $this->sender->send($business, $message);
    }

    public function verifyWebhook(array $params): ?string
    {
        // Telegram doesn't use challenge verification like WhatsApp
        // Instead, we use a secret token in the webhook URL
        return null;
    }

    public function validateSignature(string $payload, string $signature): bool
    {
        // Telegram uses a secret token in the URL path instead of signature validation
        $expectedToken = config('telegram.webhook_secret');
        return hash_equals($expectedToken, $signature);
    }

    public function setWebhook(string $botToken, string $webhookUrl, ?string $secretToken = null): bool
    {
        try {
            $url = "https://api.telegram.org/bot{$botToken}/setWebhook";

            $params = [
                'url' => $webhookUrl,
            ];

            if ($secretToken) {
                $params['secret_token'] = $secretToken;
            }

            $response = \Illuminate\Support\Facades\Http::post($url, $params);

            if ($response->successful() && $response->json('ok')) {
                Log::info('Telegram webhook set successfully', ['url' => $webhookUrl]);
                return true;
            }

            Log::error('Failed to set Telegram webhook', ['response' => $response->json()]);
            return false;
        } catch (\Throwable $e) {
            Log::error('Error setting Telegram webhook', ['error' => $e->getMessage()]);
            return false;
        }
    }
}
