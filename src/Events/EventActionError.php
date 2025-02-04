<?php

namespace Comhon\CustomAction\Events;

use Comhon\CustomAction\Models\EventAction;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Throwable;

class EventActionError
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public EventAction $eventAction, public Throwable $th) {}
}
