<?php

namespace App\Events;

use App\Actions\BadAction;
use Comhon\CustomAction\Contracts\CustomEventInterface;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BadEvent implements CustomEventInterface
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
            BadAction::class,
        ];
    }

    /**
     * Get event binding schema
     */
    public static function getBindingSchema(): array
    {
        return [
            'name' => 'string',
        ];
    }

    /**
     * Get event binding values
     */
    public function getBindingValues(): array
    {
        return [
            'company' => 'bad',
        ];
    }
}
