<?php

namespace App\Events;

use App\Enums\ActionStatus;
use App\Models\ClientAction;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ActionStatusChanged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public ClientAction $action,
        public ActionStatus $oldStatus,
        public ActionStatus $newStatus,
    ) {}
}
