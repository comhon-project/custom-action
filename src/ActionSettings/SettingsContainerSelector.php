<?php

namespace Comhon\CustomAction\ActionSettings;

use Comhon\CustomAction\Contracts\ActionScopedSettingsResolverInterface;
use Comhon\CustomAction\Facades\BindingsScoper;
use Comhon\CustomAction\Models\Action;
use Comhon\CustomAction\Models\ActionSettingsContainer;

class SettingsContainerSelector
{
    public static function select(Action $action, ?array $bindings): ActionSettingsContainer
    {
        if (! $action->actionSettings) {
            throw new \Exception('missing action settings');
        }
        if ($bindings === null) {
            return $action->actionSettings;
        }
        $possibleSettings = BindingsScoper::getActionScopedSettings($action->actionSettings, $bindings);

        if (count($possibleSettings) == 0) {
            return $action->actionSettings;
        }
        if (count($possibleSettings) == 1) {
            foreach ($possibleSettings as $settings) {
                return $settings;
            }
        }

        $resolver = app(ActionScopedSettingsResolverInterface::class);

        return $resolver->resolve($possibleSettings, $action->getActionClass());
    }
}
