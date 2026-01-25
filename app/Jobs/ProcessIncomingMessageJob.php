<?php

namespace App\Jobs;

use App\Actions\Chat\ProcessIncomingMessageAction;
use App\Actions\Chat\SendResponseAction;
use App\DataTransferObjects\IncomingMessageDTO;
use App\Models\Business;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessIncomingMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 5;

    public function __construct(
        private array $payload,
        private int $businessId,
        private string $platform,
    ) {}

    public function handle(
        ProcessIncomingMessageAction $processAction,
        SendResponseAction $sendAction,
    ): void {
        $business = Business::find($this->businessId);

        if (!$business || !$business->is_active) {
            Log::warning('Business not found or inactive', ['business_id' => $this->businessId]);
            return;
        }

        try {
            // Parse the incoming message
            $incomingMessage = match ($this->platform) {
                'whatsapp' => IncomingMessageDTO::fromWhatsApp($this->payload),
                'telegram' => IncomingMessageDTO::fromTelegram($this->payload),
                default => throw new \InvalidArgumentException("Unknown platform: {$this->platform}"),
            };

            // Process the message and get response
            $response = $processAction->execute($incomingMessage, $business);

            // Send the response
            if ($response) {
                $sendAction->execute($business, $response);
            }
        } catch (\Throwable $e) {
            Log::error('Error processing incoming message', [
                'error' => $e->getMessage(),
                'platform' => $this->platform,
                'business_id' => $this->businessId,
                'payload' => $this->payload,
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessIncomingMessageJob failed permanently', [
            'error' => $exception->getMessage(),
            'platform' => $this->platform,
            'business_id' => $this->businessId,
        ]);
    }
}
