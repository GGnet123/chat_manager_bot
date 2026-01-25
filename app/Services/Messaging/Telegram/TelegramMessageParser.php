<?php

namespace App\Services\Messaging\Telegram;

use App\DataTransferObjects\IncomingMessageDTO;
use Illuminate\Support\Facades\Log;

class TelegramMessageParser
{
    public function parse(array $payload): ?IncomingMessageDTO
    {
        try {
            // Check if this is a message update
            if (!isset($payload['message']) && !isset($payload['edited_message'])) {
                Log::debug('Telegram payload is not a message', ['payload' => $payload]);
                return null;
            }

            $message = $payload['message'] ?? $payload['edited_message'];

            // Only process text messages for now
            if (!isset($message['text'])) {
                Log::debug('Telegram message is not text', ['message' => $message]);
                return null;
            }

            // Ignore messages from groups/channels unless it's a direct mention
            $chatType = $message['chat']['type'] ?? 'private';
            if ($chatType !== 'private' && !$this->isBotMentioned($message)) {
                return null;
            }

            return IncomingMessageDTO::fromTelegram($payload);
        } catch (\Throwable $e) {
            Log::error('Failed to parse Telegram message', [
                'error' => $e->getMessage(),
                'payload' => $payload,
            ]);
            return null;
        }
    }

    private function isBotMentioned(array $message): bool
    {
        $entities = $message['entities'] ?? [];

        foreach ($entities as $entity) {
            if ($entity['type'] === 'mention' || $entity['type'] === 'bot_command') {
                return true;
            }
        }

        return false;
    }

    public function extractChatId(array $payload): ?string
    {
        $message = $payload['message'] ?? $payload['edited_message'] ?? null;
        return isset($message['chat']['id']) ? (string) $message['chat']['id'] : null;
    }
}
