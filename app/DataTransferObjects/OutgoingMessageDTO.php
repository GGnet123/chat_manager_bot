<?php

namespace App\DataTransferObjects;

use App\Enums\Platform;

readonly class OutgoingMessageDTO
{
    public function __construct(
        public Platform $platform,
        public string $recipientId,
        public string $content,
        public ?string $replyToMessageId = null,
        public array $metadata = [],
    ) {}

    public static function forWhatsApp(string $phoneNumber, string $content, ?string $replyTo = null): self
    {
        return new self(
            platform: Platform::WhatsApp,
            recipientId: $phoneNumber,
            content: $content,
            replyToMessageId: $replyTo,
        );
    }

    public static function forTelegram(string $chatId, string $content, ?string $replyTo = null): self
    {
        return new self(
            platform: Platform::Telegram,
            recipientId: $chatId,
            content: $content,
            replyToMessageId: $replyTo,
        );
    }
}
