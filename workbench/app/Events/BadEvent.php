<?php

namespace App\Events;

use App\Actions\BadAction;
use Comhon\CustomAction\Contracts\CustomEventInterface;
use Comhon\CustomAction\Contracts\HasContextInterface;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * event that has an action that doesn't implement CustomAction interface
 */
class BadEvent implements CustomEventInterface, HasContextInterface
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
     * Get event context schema
     */
    public static function getContextSchema(): array
    {
        return [
            'name' => 'string',
        ];
    }

    /**
     * Get event context values
     */
    public function getContext(): array
    {
        return [
            'company' => 'bad',
        ];
    }
}
