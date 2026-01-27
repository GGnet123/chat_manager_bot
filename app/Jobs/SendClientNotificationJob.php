<?php

namespace App\Jobs;

use App\DataTransferObjects\OutgoingMessageDTO;
use App\Enums\ActionStatus;
use App\Enums\ActionType;
use App\Enums\Platform;
use App\Models\ClientAction;
use App\Models\ConversationMessage;
use App\Services\Messaging\Telegram\TelegramMessageSender;
use App\Services\Messaging\WhatsApp\WhatsAppMessageSender;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendClientNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private ClientAction $action,
        private ActionStatus $newStatus,
    ) {}

    public function handle(
        TelegramMessageSender $telegramSender,
        WhatsAppMessageSender $whatsAppSender,
    ): void {
        $client = $this->action->client;
        $business = $this->action->business;
        $conversation = $this->action->conversation;

        if (!$client || !$business) {
            Log::warning('Cannot send client notification: missing client or business', [
                'action_id' => $this->action->id,
            ]);
            return;
        }

        // Build the notification message
        $message = $this->buildNotificationMessage();

        // Determine platform and recipient
        $platform = $client->platform;
        $recipientId = $platform === Platform::WhatsApp
            ? $client->phone
            : $client->telegram_id;

        if (!$recipientId) {
            Log::warning('Cannot send client notification: no recipient ID', [
                'action_id' => $this->action->id,
                'platform' => $platform?->value,
            ]);
            return;
        }

        $outgoingMessage = new OutgoingMessageDTO(
            platform: $platform,
            recipientId: $recipientId,
            content: $message,
        );

        // Send message via appropriate sender
        $success = match ($platform) {
            Platform::Telegram => $telegramSender->send($business, $outgoingMessage),
            Platform::WhatsApp => $whatsAppSender->send($business, $outgoingMessage),
            default => false,
        };

        if ($success) {
            Log::info('Client notification sent successfully', [
                'action_id' => $this->action->id,
                'status' => $this->newStatus->value,
                'platform' => $platform->value,
            ]);

            // Store message in conversation if available
            if ($conversation) {
                ConversationMessage::create([
                    'conversation_id' => $conversation->id,
                    'role' => 'assistant',
                    'content' => $message,
                ]);
            }
        } else {
            Log::error('Failed to send client notification', [
                'action_id' => $this->action->id,
                'status' => $this->newStatus->value,
                'platform' => $platform->value,
            ]);
        }
    }

    private function buildNotificationMessage(): string
    {
        $actionType = $this->action->action;
        $notes = $this->action->notes;

        return match ($this->newStatus) {
            ActionStatus::Completed => $this->buildCompletedMessage($actionType, $notes),
            ActionStatus::Failed => $this->buildFailedMessage($actionType, $notes),
            ActionStatus::Cancelled => $this->buildCancelledMessage($actionType, $notes),
            default => '',
        };
    }

    private function buildCompletedMessage(ActionType $actionType, ?string $notes): string
    {
        $base = match ($actionType) {
            ActionType::Reservation => $this->buildReservationConfirmation(),
            ActionType::Order => "Ваш заказ подтверждён! ",
            ActionType::Callback => "Мы свяжемся с вами в ближайшее время.",
            ActionType::Inquiry => "Ваш запрос обработан.",
            ActionType::Complaint => "Ваша жалоба рассмотрена. Спасибо за обратную связь.",
            ActionType::Other => "Ваш запрос выполнен.",
        };

        if ($notes) {
            $base .= "\n\n" . $notes;
        }

        return $base;
    }

    private function buildReservationConfirmation(): string
    {
        $details = $this->action->details ?? [];

        $message = "Ваше бронирование подтверждено! ";

        if (!empty($details['date'])) {
            $message .= "\n📅 Дата: " . $details['date'];
        }
        if (!empty($details['time'])) {
            $message .= "\n🕐 Время: " . $details['time'];
        }
        if (!empty($details['party_size'])) {
            $message .= "\n👥 Количество гостей: " . $details['party_size'];
        }

        $message .= "\n\nЖдём вас!";

        return $message;
    }

    private function buildFailedMessage(ActionType $actionType, ?string $notes): string
    {
        $base = match ($actionType) {
            ActionType::Reservation => "К сожалению, мы не можем подтвердить ваше бронирование.",
            ActionType::Order => "К сожалению, мы не можем выполнить ваш заказ.",
            default => "К сожалению, мы не можем выполнить ваш запрос.",
        };

        if ($notes) {
            $base .= "\n\nПричина: " . $notes;
        }

        $base .= "\n\nПожалуйста, свяжитесь с нами для уточнения деталей.";

        return $base;
    }

    private function buildCancelledMessage(ActionType $actionType, ?string $notes): string
    {
        $base = match ($actionType) {
            ActionType::Reservation => "Ваше бронирование отменено.",
            ActionType::Order => "Ваш заказ отменён.",
            default => "Ваш запрос отменён.",
        };

        if ($notes) {
            $base .= "\n\nПримечание: " . $notes;
        }

        return $base;
    }
}
