<?php

namespace App\Enums;

enum Platform: string
{
    case WhatsApp = 'whatsapp';
    case Telegram = 'telegram';

    public function label(): string
    {
        return match ($this) {
            self::WhatsApp => 'WhatsApp',
            self::Telegram => 'Telegram',
        };
    }
}
