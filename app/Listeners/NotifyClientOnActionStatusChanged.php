<?php

namespace App\Listeners;

use App\Enums\ActionStatus;
use App\Events\ActionStatusChanged;
use App\Jobs\SendClientNotificationJob;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyClientOnActionStatusChanged implements ShouldQueue
{
    public function handle(ActionStatusChanged $event): void
    {
        // Only notify client when action is completed, failed, or cancelled
        $notifiableStatuses = [
            ActionStatus::Completed,
            ActionStatus::Failed,
            ActionStatus::Cancelled,
        ];

        if (!in_array($event->newStatus, $notifiableStatuses)) {
            return;
        }

        // Dispatch job to send notification to client
        SendClientNotificationJob::dispatch(
            $event->action,
            $event->newStatus
        );
    }
}
