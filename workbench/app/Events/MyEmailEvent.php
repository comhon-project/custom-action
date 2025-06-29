<?php

namespace App\Events;

use Comhon\CustomAction\Actions\Email\SendAutomaticEmail;
use Comhon\CustomAction\Contracts\CustomEventInterface;
use Comhon\CustomAction\Contracts\ExposeContextInterface;
use Comhon\CustomAction\Rules\RuleHelper;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MyEmailEvent implements CustomEventInterface, ExposeContextInterface
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public static function getAllowedActions(): array
    {
        return [
            SendAutomaticEmail::class,
        ];
    }

    public static function getContextSchema(): array
    {
        return [
            'logo' => RuleHelper::getRuleName('is').':stored-file',
            'user' => RuleHelper::getRuleName('is').':mailable-entity',
            'user.name' => 'string',
            'user.email' => 'email',
            'responsibles' => 'array',
            'responsibles.*' => 'array',
            'responsibles.*.email' => 'email',
        ];
    }
}
