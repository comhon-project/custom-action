<?php

namespace Comhon\CustomAction\Contracts;

use Comhon\CustomAction\Models\ScopedSetting;

interface ScopedSettingResolverInterface
{
    /**
     * reslove conflicts between several action scoped settings
     */
    public function resolve(array $scopedSettings, string $actionClass): ScopedSetting;
}
