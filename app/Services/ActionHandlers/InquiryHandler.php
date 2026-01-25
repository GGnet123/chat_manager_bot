<?php

namespace App\Services\ActionHandlers;

use App\DataTransferObjects\ParsedActionDTO;
use App\Enums\ActionType;

class InquiryHandler extends AbstractActionHandler
{
    public function validate(ParsedActionDTO $action): bool
    {
        $details = $action->details;

        // Inquiries should have some content
        if (empty($details['topic']) && empty($details['details']) && empty($details['issue'])) {
            return false;
        }

        return true;
    }

    public function supports(string $actionType): bool
    {
        return in_array($actionType, [
            ActionType::Inquiry->value,
            ActionType::Complaint->value,
            ActionType::Callback->value,
            ActionType::Other->value,
        ]);
    }

    protected function determinePriority(ParsedActionDTO $action): string
    {
        $details = $action->details;

        // Complaints typically need faster attention
        if ($action->type === ActionType::Complaint) {
            $urgency = $details['urgency'] ?? 'medium';
            return $urgency === 'high' ? 'high' : 'normal';
        }

        // Callback requests with specific times
        if ($action->type === ActionType::Callback && !empty($details['preferred_time'])) {
            return 'high';
        }

        return $details['priority'] ?? 'normal';
    }
}
