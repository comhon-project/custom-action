<?php

namespace Comhon\CustomAction\Actions;

use Comhon\CustomAction\Facades\CustomActionModelResolver;
use Comhon\CustomAction\Models\ManualAction;

trait HandleManualActionTrait
{
    public static function handleManual(...$args)
    {
        $type = CustomActionModelResolver::getUniqueName(static::class);
        static::dispatch(ManualAction::findOrFail($type), ...$args);
    }
}
