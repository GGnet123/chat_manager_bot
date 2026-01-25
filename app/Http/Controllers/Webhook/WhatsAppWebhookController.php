<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessIncomingMessageJob;
use App\Models\Business;
use App\Services\Messaging\WhatsApp\WhatsAppMessageParser;
use App\Services\Messaging\WhatsApp\WhatsAppService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    public function __construct(
        private WhatsAppService $whatsAppService,
        private WhatsAppMessageParser $parser,
    ) {}

    public function verify(Request $request): Response
    {
        $challenge = $this->whatsAppService->verifyWebhook($request->query());

        if ($challenge) {
            return response($challenge, 200);
        }

        return response('Verification failed', 403);
    }

    public function handle(Request $request): JsonResponse
    {
        $payload = $request->all();

        Log::debug('WhatsApp webhook received', ['payload' => $payload]);

        // Extract phone number ID to find the business
        $phoneNumberId = $this->parser->extractPhoneNumberId($payload);

        if (!$phoneNumberId) {
            Log::warning('Could not extract phone number ID from WhatsApp payload');
            return response()->json(['status' => 'ok']);
        }

        // Find business by WhatsApp phone ID
        $business = Business::where('whatsapp_phone_id', $phoneNumberId)
            ->where('is_active', true)
            ->first();

        if (!$business) {
            Log::warning('No business found for WhatsApp phone ID', ['phone_id' => $phoneNumberId]);
            return response()->json(['status' => 'ok']);
        }

        // Only process text messages
        if (!$this->parser->isTextMessage($payload)) {
            Log::debug('Ignoring non-text WhatsApp message');
            return response()->json(['status' => 'ok']);
        }

        // Dispatch job to process the message
        ProcessIncomingMessageJob::dispatch($payload, $business->id, 'whatsapp');

        return response()->json(['status' => 'ok']);
    }
}
