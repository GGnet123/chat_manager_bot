<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessIncomingMessageJob;
use App\Models\Business;
use App\Services\Messaging\Telegram\TelegramMessageParser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelegramWebhookController extends Controller
{
    public function __construct(
        private TelegramMessageParser $parser,
    ) {}

    public function handle(Request $request, string $token): JsonResponse
    {
        $payload = $request->all();

        Log::debug('Telegram webhook received', ['webhook_id' => $token]);

        // Efficient lookup using indexed webhook_id column
        $business = Business::where('telegram_webhook_id', $token)
            ->where('is_active', true)
            ->first();

        if (!$business) {
            Log::warning('No business found for Telegram webhook', ['webhook_id' => $token]);
            return response()->json(['ok' => true]);
        }

        // Verify webhook secret if configured (Telegram sends it in X-Telegram-Bot-Api-Secret-Token header)
        $secretHeader = $request->header('X-Telegram-Bot-Api-Secret-Token');
        if ($business->telegram_webhook_secret && $secretHeader) {
            if (!hash_equals($business->telegram_webhook_secret, $secretHeader)) {
                Log::warning('Telegram webhook secret mismatch', [
                    'business_id' => $business->id,
                    'webhook_id' => $token,
                ]);
                return response()->json(['ok' => true]); // Don't reveal error to attacker
            }
        }

        // Parse message to check if it's valid
        $message = $this->parser->parse($payload);

        if (!$message) {
            Log::debug('Ignoring non-processable Telegram update');
            return response()->json(['ok' => true]);
        }

        // Dispatch job to process the message
        ProcessIncomingMessageJob::dispatch($payload, $business->id, 'telegram');

        return response()->json(['ok' => true]);
    }
}
