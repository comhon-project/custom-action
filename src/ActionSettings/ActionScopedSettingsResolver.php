<?php

namespace Comhon\CustomAction\ActionSettings;

use Comhon\CustomAction\Contracts\ActionScopedSettingsResolverInterface;
use Comhon\CustomAction\Exceptions\UnresolvableActionScopedSettingsException;
use Comhon\CustomAction\Models\ActionScopedSettings;

class ActionScopedSettingsResolver implements ActionScopedSettingsResolverInterface
{
    /**
     * reslove conflicts between several action scoped settings
     */
    public function resolve(array $actionScopedSettings, string $actionClass): ActionScopedSettings
    {
        throw new UnresolvableActionScopedSettingsException(
            'cannot resolve conflict between several action scoped settings'
        );
    }
}
