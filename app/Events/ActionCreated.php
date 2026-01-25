<?php

namespace App\Events;

use App\Models\ClientAction;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ActionCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public ClientAction $action,
    ) {}
}
