<?php

namespace Comhon\CustomAction\Actions;

use Comhon\CustomAction\Facades\CustomActionModelResolver;
use Comhon\CustomAction\Models\Action;
use Comhon\CustomAction\Models\ManualAction;

trait CallableManually
{
    public function getAction(): Action
    {
        $type = CustomActionModelResolver::getUniqueName(static::class);
        $action = ManualAction::find($type);
        if (! $action) {
            throw new \Exception("manual action $type not found");
        }

        return $action;
    }
}
