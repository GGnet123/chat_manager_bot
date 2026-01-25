<?php

namespace App\DataTransferObjects;

use App\Enums\ActionType;

readonly class ParsedActionDTO
{
    public function __construct(
        public ActionType $type,
        public array $details,
        public ?string $clientName = null,
        public ?string $clientPhone = null,
    ) {}

    public static function fromGptResponse(string $actionType, array $details): self
    {
        $type = ActionType::tryFrom($actionType) ?? ActionType::Other;

        return new self(
            type: $type,
            details: $details,
            clientName: $details['client_name'] ?? $details['name'] ?? null,
            clientPhone: $details['client_phone'] ?? $details['phone'] ?? null,
        );
    }
}
