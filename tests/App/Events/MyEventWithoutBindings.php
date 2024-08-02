<?php

namespace App\Events;

use App\Actions\MyActionWithoutBindings;
use Comhon\CustomAction\Contracts\CustomEventInterface;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MyEventWithoutBindings implements CustomEventInterface
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct() {}

    /**
     * Get actions that might be attached to event
     */
    public static function getAllowedActions(): array
    {
        return [
            MyActionWithoutBindings::class,
        ];
    }
}
