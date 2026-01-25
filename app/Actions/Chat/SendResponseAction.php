<?php

namespace App\Actions\Chat;

use App\Contracts\Messaging\MessengerInterface;
use App\DataTransferObjects\OutgoingMessageDTO;
use App\Enums\Platform;
use App\Models\Business;
use App\Services\Messaging\Telegram\TelegramService;
use App\Services\Messaging\WhatsApp\WhatsAppService;
use Illuminate\Support\Facades\Log;

class SendResponseAction
{
    public function __construct(
        private WhatsAppService $whatsAppService,
        private TelegramService $telegramService,
    ) {}

    public function execute(Business $business, OutgoingMessageDTO $message): bool
    {
        $messenger = $this->getMessenger($message->platform);

        $result = $messenger->sendMessage($business, $message);

        if (!$result) {
            Log::error('Failed to send response', [
                'platform' => $message->platform->value,
                'recipient' => $message->recipientId,
                'business_id' => $business->id,
            ]);
        }

        return $result;
    }

    private function getMessenger(Platform $platform): MessengerInterface
    {
        return match ($platform) {
            Platform::WhatsApp => $this->whatsAppService,
            Platform::Telegram => $this->telegramService,
        };
    }
}
