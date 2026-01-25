<?php

namespace App\Jobs;

use App\Actions\Chat\SendResponseAction;
use App\DataTransferObjects\OutgoingMessageDTO;
use App\Models\Business;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendMessengerResponseJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 10;

    public function __construct(
        private int $businessId,
        private OutgoingMessageDTO $message,
    ) {}

    public function handle(SendResponseAction $sendAction): void
    {
        $business = Business::find($this->businessId);

        if (!$business) {
            Log::warning('Business not found for sending response', ['business_id' => $this->businessId]);
            return;
        }

        $result = $sendAction->execute($business, $this->message);

        if (!$result) {
            throw new \RuntimeException('Failed to send messenger response');
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('SendMessengerResponseJob failed permanently', [
            'error' => $exception->getMessage(),
            'business_id' => $this->businessId,
            'platform' => $this->message->platform->value,
            'recipient' => $this->message->recipientId,
        ]);
    }
}
