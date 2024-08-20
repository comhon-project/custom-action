<?php

namespace Comhon\CustomAction\Actions;

use Comhon\CustomAction\Contracts\BindingsContainerInterface;
use Comhon\CustomAction\Facades\CustomActionModelResolver;
use Comhon\CustomAction\Models\ManualAction;

trait HandleManualAction
{
    public static function handleManual(?BindingsContainerInterface $bindingsContainer = null, ...$args)
    {
        $type = CustomActionModelResolver::getUniqueName(static::class);
        $action = ManualAction::findOrFail($type);

        $bindings = $bindingsContainer?->getBindingValues() ?? [];
        $settingsContainer = $action->actionSettings->getSettingsContainer($bindings);

        static::dispatch($settingsContainer, $bindingsContainer, ...$args);
    }
}
