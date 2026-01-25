<?php

namespace App\Services\Messaging\WhatsApp;

use App\Contracts\Messaging\MessengerInterface;
use App\DataTransferObjects\IncomingMessageDTO;
use App\DataTransferObjects\OutgoingMessageDTO;
use App\Models\Business;
use Illuminate\Support\Facades\Log;

class WhatsAppService implements MessengerInterface
{
    public function __construct(
        private WhatsAppMessageParser $parser,
        private WhatsAppMessageSender $sender,
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
        $mode = $params['hub_mode'] ?? null;
        $token = $params['hub_verify_token'] ?? null;
        $challenge = $params['hub_challenge'] ?? null;

        if ($mode === 'subscribe' && $token === config('whatsapp.verify_token')) {
            Log::info('WhatsApp webhook verified');
            return $challenge;
        }

        Log::warning('WhatsApp webhook verification failed', ['params' => $params]);
        return null;
    }

    public function validateSignature(string $payload, string $signature): bool
    {
        $expectedSignature = hash_hmac('sha256', $payload, config('whatsapp.app_secret'));

        // The signature from WhatsApp is prefixed with "sha256="
        $providedSignature = str_replace('sha256=', '', $signature);

        return hash_equals($expectedSignature, $providedSignature);
    }
}
