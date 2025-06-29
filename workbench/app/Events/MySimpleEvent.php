<?php

namespace App\Events;

use App\Actions\QueuedEventAction;
use App\Actions\SimpleEventAction;
use Comhon\CustomAction\Contracts\CustomEventInterface;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MySimpleEvent implements CustomEventInterface
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public static function getAllowedActions(): array
    {
        return [
            SimpleEventAction::class,
            QueuedEventAction::class,
        ];
    }
}
