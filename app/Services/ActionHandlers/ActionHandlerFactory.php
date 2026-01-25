<?php

namespace App\Services\ActionHandlers;

use App\Contracts\ActionHandlers\ActionHandlerInterface;
use App\Enums\ActionType;

class ActionHandlerFactory
{
    private array $handlers = [];

    public function __construct(
        ReservationHandler $reservationHandler,
        OrderHandler $orderHandler,
        InquiryHandler $inquiryHandler,
    ) {
        $this->handlers = [
            ActionType::Reservation->value => $reservationHandler,
            ActionType::Order->value => $orderHandler,
            ActionType::Inquiry->value => $inquiryHandler,
            ActionType::Complaint->value => $inquiryHandler, // Use inquiry handler for complaints
            ActionType::Callback->value => $inquiryHandler, // Use inquiry handler for callbacks
            ActionType::Other->value => $inquiryHandler, // Use inquiry handler for other
        ];
    }

    public function getHandler(string $actionType): ActionHandlerInterface
    {
        return $this->handlers[$actionType] ?? $this->handlers[ActionType::Other->value];
    }

    public function supports(string $actionType): bool
    {
        return isset($this->handlers[$actionType]);
    }
}
