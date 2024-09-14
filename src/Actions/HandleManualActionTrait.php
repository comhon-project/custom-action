<?php

namespace Comhon\CustomAction\Actions;

use Comhon\CustomAction\ActionSettings\SettingsContainerSelector;
use Comhon\CustomAction\Contracts\BindingsContainerInterface;
use Comhon\CustomAction\Facades\CustomActionModelResolver;
use Comhon\CustomAction\Models\ManualAction;

trait HandleManualActionTrait
{
    public static function handleManual(?BindingsContainerInterface $bindingsContainer = null, ...$args)
    {
        $type = CustomActionModelResolver::getUniqueName(static::class);

        $settingsContainer = SettingsContainerSelector::select(
            ManualAction::findOrFail($type),
            $bindingsContainer?->getBindingValues()
        );

        static::dispatch($settingsContainer, $bindingsContainer, ...$args);
    }
}
