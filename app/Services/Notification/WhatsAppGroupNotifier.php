<?php

namespace App\Services\Notification;

use App\Models\Business;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppGroupNotifier
{
    public function send(Business $business, string $chatId, string $message): bool
    {
        // Note: WhatsApp Business API doesn't support group messaging directly
        // This would need to be implemented via WhatsApp Business Groups or
        // a workaround using individual messages to group members

        try {
            $url = config('whatsapp.api_url') . '/' . config('whatsapp.api_version') . '/' . $business->whatsapp_phone_id . '/messages';

            $response = Http::withToken($business->whatsapp_access_token)
                ->post($url, [
                    'messaging_product' => 'whatsapp',
                    'to' => $chatId,
                    'type' => 'text',
                    'text' => [
                        'body' => $message,
                    ],
                ]);

            if ($response->successful()) {
                Log::info('WhatsApp group notification sent', [
                    'chat_id' => $chatId,
                    'business_id' => $business->id,
                ]);
                return true;
            }

            Log::error('WhatsApp group notification failed', [
                'chat_id' => $chatId,
                'response' => $response->body(),
            ]);
            return false;
        } catch (\Throwable $e) {
            Log::error('WhatsApp group notification error', [
                'error' => $e->getMessage(),
                'chat_id' => $chatId,
            ]);
            return false;
        }
    }
}
