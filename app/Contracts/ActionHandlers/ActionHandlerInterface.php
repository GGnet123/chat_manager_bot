<?php

namespace App\Contracts\ActionHandlers;

use App\DataTransferObjects\ParsedActionDTO;
use App\Models\ClientAction;
use App\Models\Conversation;

interface ActionHandlerInterface
{
    public function handle(ParsedActionDTO $action, Conversation $conversation): ClientAction;

    public function validate(ParsedActionDTO $action): bool;

    public function supports(string $actionType): bool;
}
