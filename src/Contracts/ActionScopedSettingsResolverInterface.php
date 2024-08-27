<?php

namespace Comhon\CustomAction\Contracts;

use Comhon\CustomAction\Models\ActionScopedSettings;

interface ActionScopedSettingsResolverInterface
{
    /**
     * reslove conflicts between several action scoped settings
     */
    public function resolve(array $actionScopedSettings, string $actionClass): ActionScopedSettings;
}
