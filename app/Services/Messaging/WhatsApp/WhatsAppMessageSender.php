<?php

namespace App\Services\Messaging\WhatsApp;

use App\DataTransferObjects\OutgoingMessageDTO;
use App\Models\Business;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppMessageSender
{
    public function send(Business $business, OutgoingMessageDTO $message): bool
    {
        $url = $this->buildApiUrl($business->whatsapp_phone_id);

        try {
            $payload = $this->buildPayload($message);

            $response = Http::withToken($business->whatsapp_access_token)
                ->post($url, $payload);

            if ($response->successful()) {
                Log::info('WhatsApp message sent successfully', [
                    'recipient' => $message->recipientId,
                    'response' => $response->json(),
                ]);
                return true;
            }

            Log::error('WhatsApp API error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return false;
        } catch (\Throwable $e) {
            Log::error('Failed to send WhatsApp message', [
                'error' => $e->getMessage(),
                'recipient' => $message->recipientId,
            ]);
            return false;
        }
    }

    private function buildApiUrl(string $phoneNumberId): string
    {
        $baseUrl = config('whatsapp.api_url');
        $version = config('whatsapp.api_version');

        return "{$baseUrl}/{$version}/{$phoneNumberId}/messages";
    }

    private function buildPayload(OutgoingMessageDTO $message): array
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $message->recipientId,
            'type' => 'text',
            'text' => [
                'preview_url' => false,
                'body' => $message->content,
            ],
        ];

        if ($message->replyToMessageId) {
            $payload['context'] = [
                'message_id' => $message->replyToMessageId,
            ];
        }

        return $payload;
    }
}
