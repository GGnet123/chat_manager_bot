<?php

namespace App\Services\ActionHandlers;

use App\DataTransferObjects\ParsedActionDTO;
use App\Enums\ActionType;

class ReservationHandler extends AbstractActionHandler
{
    public function validate(ParsedActionDTO $action): bool
    {
        $details = $action->details;

        // Required fields for a reservation
        $required = ['date', 'time'];

        foreach ($required as $field) {
            if (empty($details[$field])) {
                return false;
            }
        }

        // Validate date format
        if (!strtotime($details['date'])) {
            return false;
        }

        // Validate party size if provided
        if (isset($details['party_size']) && (!is_numeric($details['party_size']) || $details['party_size'] < 1)) {
            return false;
        }

        return true;
    }

    public function supports(string $actionType): bool
    {
        return $actionType === ActionType::Reservation->value;
    }

    protected function determinePriority(ParsedActionDTO $action): string
    {
        $details = $action->details;

        // Check if reservation is for today or tomorrow
        $date = strtotime($details['date'] ?? '');
        $today = strtotime('today');
        $tomorrow = strtotime('tomorrow');

        if ($date && $date <= $tomorrow) {
            return 'high';
        }

        // Large party sizes get higher priority
        $partySize = $details['party_size'] ?? 0;
        if ($partySize >= 8) {
            return 'high';
        }

        return 'normal';
    }
}
