<?php

namespace App\Contracts\Messaging;

use App\DataTransferObjects\IncomingMessageDTO;
use App\DataTransferObjects\OutgoingMessageDTO;
use App\Models\Business;

interface MessengerInterface
{
    public function parseIncomingMessage(array $payload): ?IncomingMessageDTO;

    public function sendMessage(Business $business, OutgoingMessageDTO $message): bool;

    public function verifyWebhook(array $params): ?string;

    public function validateSignature(string $payload, string $signature): bool;
}
