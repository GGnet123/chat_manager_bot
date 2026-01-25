<?php

namespace App\DataTransferObjects;

use App\Enums\Platform;

readonly class IncomingMessageDTO
{
    public function __construct(
        public Platform $platform,
        public string $senderId,
        public string $content,
        public string $messageId,
        public ?string $senderName = null,
        public ?string $businessPhoneId = null,
        public ?string $telegramBotId = null,
        public array $metadata = [],
    ) {}

    public static function fromWhatsApp(array $data): self
    {
        $message = $data['entry'][0]['changes'][0]['value']['messages'][0] ?? null;
        $contact = $data['entry'][0]['changes'][0]['value']['contacts'][0] ?? null;
        $metadata = $data['entry'][0]['changes'][0]['value']['metadata'] ?? [];

        if (!$message) {
            throw new \InvalidArgumentException('Invalid WhatsApp message payload');
        }

        return new self(
            platform: Platform::WhatsApp,
            senderId: $message['from'],
            content: $message['text']['body'] ?? '',
            messageId: $message['id'],
            senderName: $contact['profile']['name'] ?? null,
            businessPhoneId: $metadata['phone_number_id'] ?? null,
            metadata: [
                'timestamp' => $message['timestamp'] ?? null,
                'type' => $message['type'] ?? 'text',
            ],
        );
    }

    public static function fromTelegram(array $data): self
    {
        $message = $data['message'] ?? $data['edited_message'] ?? null;

        if (!$message) {
            throw new \InvalidArgumentException('Invalid Telegram message payload');
        }

        $from = $message['from'] ?? [];

        return new self(
            platform: Platform::Telegram,
            senderId: (string) ($from['id'] ?? ''),
            content: $message['text'] ?? '',
            messageId: (string) ($message['message_id'] ?? ''),
            senderName: trim(($from['first_name'] ?? '') . ' ' . ($from['last_name'] ?? '')),
            telegramBotId: null,
            metadata: [
                'chat_id' => $message['chat']['id'] ?? null,
                'chat_type' => $message['chat']['type'] ?? 'private',
                'username' => $from['username'] ?? null,
            ],
        );
    }
}
