<?php

namespace App\Events;

use App\Actions\BadAction;
use Comhon\CustomAction\Contracts\CustomEventInterface;
use Comhon\CustomAction\Contracts\ExposeContextInterface;
use Comhon\CustomAction\Contracts\FormatContextInterface;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * event that has an action that doesn't implement CustomAction interface
 */
class BadEvent implements CustomEventInterface, ExposeContextInterface, FormatContextInterface
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct() {}

    public static function getAllowedActions(): array
    {
        return [
            BadAction::class,
        ];
    }

    public static function getContextSchema(): array
    {
        return [
            'name' => 'string',
        ];
    }

    public function formatContext(): array
    {
        return [
            'company' => 'bad',
        ];
    }
}
