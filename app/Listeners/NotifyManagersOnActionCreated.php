<?php

namespace App\Listeners;

use App\Events\ActionCreated;
use App\Services\Notification\NotificationDispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyManagersOnActionCreated implements ShouldQueue
{
    public function __construct(
        private NotificationDispatcher $notificationDispatcher,
    ) {}

    public function handle(ActionCreated $event): void
    {
        $this->notificationDispatcher->dispatchForAction($event->action);
    }
}
