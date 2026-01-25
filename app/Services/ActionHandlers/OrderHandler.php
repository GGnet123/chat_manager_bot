<?php

namespace App\Services\ActionHandlers;

use App\DataTransferObjects\ParsedActionDTO;
use App\Enums\ActionType;

class OrderHandler extends AbstractActionHandler
{
    public function validate(ParsedActionDTO $action): bool
    {
        $details = $action->details;

        // Orders should have at least items or a description
        if (empty($details['items']) && empty($details['description'])) {
            return false;
        }

        // Validate items array if provided
        if (isset($details['items']) && !is_array($details['items'])) {
            return false;
        }

        return true;
    }

    public function supports(string $actionType): bool
    {
        return $actionType === ActionType::Order->value;
    }

    protected function determinePriority(ParsedActionDTO $action): string
    {
        $details = $action->details;

        // Express/urgent orders get high priority
        if (!empty($details['urgent']) || !empty($details['express'])) {
            return 'high';
        }

        // Large orders might need priority handling
        $items = $details['items'] ?? [];
        if (count($items) >= 5) {
            return 'high';
        }

        return 'normal';
    }
}
