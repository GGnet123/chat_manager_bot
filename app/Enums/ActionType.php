<?php

namespace App\Enums;

enum ActionType: string
{
    case Reservation = 'reservation';
    case Order = 'order';
    case Inquiry = 'inquiry';
    case Complaint = 'complaint';
    case Callback = 'callback';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Reservation => 'Reservation',
            self::Order => 'Order',
            self::Inquiry => 'Inquiry',
            self::Complaint => 'Complaint',
            self::Callback => 'Callback Request',
            self::Other => 'Other',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Reservation => 'calendar',
            self::Order => 'shopping-cart',
            self::Inquiry => 'help-circle',
            self::Complaint => 'alert-triangle',
            self::Callback => 'phone',
            self::Other => 'more-horizontal',
        };
    }
}
