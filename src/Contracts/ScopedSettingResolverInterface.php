<?php

namespace Comhon\CustomAction\Contracts;

use Comhon\CustomAction\Models\ScopedSetting;
use Illuminate\Support\Collection;

interface ScopedSettingResolverInterface
{
    /**
     * reslove conflicts between several action scoped settings
     */
    public function resolve(Collection|array $scopedSettings, string $actionClass): ScopedSetting;
}
