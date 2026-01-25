<?php

namespace App\Services\ActionHandlers;

use App\Contracts\ActionHandlers\ActionHandlerInterface;
use App\DataTransferObjects\ParsedActionDTO;
use App\Events\ActionCreated;
use App\Models\ClientAction;
use App\Models\Conversation;

abstract class AbstractActionHandler implements ActionHandlerInterface
{
    public function handle(ParsedActionDTO $action, Conversation $conversation): ClientAction
    {
        $clientAction = ClientAction::create([
            'business_id' => $conversation->business_id,
            'client_id' => $conversation->client_id,
            'conversation_id' => $conversation->id,
            'action' => $action->type,
            'details' => $action->details,
            'client_name' => $action->clientName ?? $conversation->client->name,
            'client_phone' => $action->clientPhone ?? $conversation->client->phone,
            'status' => 'pending',
            'priority' => $this->determinePriority($action),
        ]);

        // Dispatch event for notification
        event(new ActionCreated($clientAction));

        return $clientAction;
    }

    protected function determinePriority(ParsedActionDTO $action): string
    {
        // Can be overridden by specific handlers
        return $action->details['urgency'] ?? $action->details['priority'] ?? 'normal';
    }

    abstract public function validate(ParsedActionDTO $action): bool;

    abstract public function supports(string $actionType): bool;
}
