<?php

namespace Comhon\CustomAction\Listeners;

use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;

class QueuedEventActionDispatcher extends EventActionDispatcher implements ShouldQueueAfterCommit
{
    /**
     * Get the name of the listener's queue connection.
     */
    public function viaConnection(): string
    {
        return config('custom-action.event_action_dispatcher.queue_connection')
            ?? config('queue.default');
    }

    /**
     * Get the name of the listener's queue.
     */
    public function viaQueue(): ?string
    {
        $connection = $this->viaConnection();

        return config('custom-action.event_action_dispatcher.queue_name')
            ?? config("queue.connections.$connection.queue");
    }
}
