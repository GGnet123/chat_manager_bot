<?php

namespace App\Services\Notification;

use App\Jobs\SendGroupNotificationJob;
use App\Models\ClientAction;
use App\Models\ManagerNotificationPreference;
use Illuminate\Support\Facades\Log;

class NotificationDispatcher
{
    public function __construct(
        private WhatsAppGroupNotifier $whatsAppNotifier,
        private TelegramGroupNotifier $telegramNotifier,
    ) {}

    public function dispatchForAction(ClientAction $action): void
    {
        $business = $action->business;

        // Get all managers with notification preferences for this business
        $preferences = ManagerNotificationPreference::where('business_id', $business->id)
            ->with('user')
            ->get();

        foreach ($preferences as $preference) {
            if (!$preference->shouldNotifyFor($action->action->value)) {
                continue;
            }

            // Queue notifications for each configured channel
            $this->queueNotifications($action, $preference);
        }
    }

    private function queueNotifications(ClientAction $action, ManagerNotificationPreference $preference): void
    {
        // WhatsApp group notifications
        if (!empty($preference->whatsapp_groups)) {
            foreach ($preference->whatsapp_groups as $groupId) {
                SendGroupNotificationJob::dispatch(
                    $action->id,
                    'whatsapp',
                    $groupId
                );
            }
        }

        // Telegram group notifications
        if (!empty($preference->telegram_groups)) {
            foreach ($preference->telegram_groups as $groupId) {
                SendGroupNotificationJob::dispatch(
                    $action->id,
                    'telegram',
                    $groupId
                );
            }
        }

        Log::info('Notifications queued for action', [
            'action_id' => $action->id,
            'user_id' => $preference->user_id,
        ]);
    }

    public function sendNotification(ClientAction $action, string $platform, string $chatId): bool
    {
        $message = $this->buildNotificationMessage($action);

        return match ($platform) {
            'whatsapp' => $this->whatsAppNotifier->send($action->business, $chatId, $message),
            'telegram' => $this->telegramNotifier->send($action->business, $chatId, $message),
            default => false,
        };
    }

    private function buildNotificationMessage(ClientAction $action): string
    {
        $emoji = $this->getActionEmoji($action->action->value);
        $client = $action->client;

        $message = "{$emoji} New {$action->action->label()}\n\n";
        $message .= "Client: {$action->client_name}";

        if ($action->client_phone) {
            $message .= " ({$action->client_phone})";
        }

        $message .= "\n";

        // Add relevant details based on action type
        $details = $action->details ?? [];

        if ($action->action->value === 'reservation') {
            if (!empty($details['date'])) {
                $message .= "Date: {$details['date']}";
                if (!empty($details['time'])) {
                    $message .= " at {$details['time']}";
                }
                $message .= "\n";
            }
            if (!empty($details['party_size'])) {
                $message .= "Party size: {$details['party_size']}\n";
            }
            if (!empty($details['special_requests'])) {
                $message .= "Special requests: {$details['special_requests']}\n";
            }
        } elseif ($action->action->value === 'order') {
            if (!empty($details['items'])) {
                $message .= "Items: " . implode(', ', array_column($details['items'], 'name')) . "\n";
            }
        } elseif (!empty($details['details'])) {
            $message .= "Details: {$details['details']}\n";
        }

        if ($action->priority === 'high') {
            $message .= "\n⚠️ HIGH PRIORITY";
        }

        return $message;
    }

    private function getActionEmoji(string $actionType): string
    {
        return match ($actionType) {
            'reservation' => '📅',
            'order' => '🛒',
            'inquiry' => '❓',
            'complaint' => '⚠️',
            'callback' => '📞',
            default => '🔔',
        };
    }
}
