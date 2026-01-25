<?php

namespace App\Services\Messaging\WhatsApp;

use App\DataTransferObjects\IncomingMessageDTO;
use Illuminate\Support\Facades\Log;

class WhatsAppMessageParser
{
    public function parse(array $payload): ?IncomingMessageDTO
    {
        try {
            // Check if this is a status update (not a message)
            $value = $payload['entry'][0]['changes'][0]['value'] ?? [];

            if (!isset($value['messages'])) {
                Log::debug('WhatsApp payload is not a message', ['payload' => $payload]);
                return null;
            }

            return IncomingMessageDTO::fromWhatsApp($payload);
        } catch (\Throwable $e) {
            Log::error('Failed to parse WhatsApp message', [
                'error' => $e->getMessage(),
                'payload' => $payload,
            ]);
            return null;
        }
    }

    public function extractPhoneNumberId(array $payload): ?string
    {
        return $payload['entry'][0]['changes'][0]['value']['metadata']['phone_number_id'] ?? null;
    }

    public function isTextMessage(array $payload): bool
    {
        $messageType = $payload['entry'][0]['changes'][0]['value']['messages'][0]['type'] ?? null;
        return $messageType === 'text';
    }
}
