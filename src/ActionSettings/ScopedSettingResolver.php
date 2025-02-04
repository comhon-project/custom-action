<?php

namespace Comhon\CustomAction\ActionSettings;

use Comhon\CustomAction\Contracts\ScopedSettingResolverInterface;
use Comhon\CustomAction\Exceptions\UnresolvableScopedSettingException;
use Comhon\CustomAction\Models\ScopedSetting;
use Illuminate\Support\Collection;

class ScopedSettingResolver implements ScopedSettingResolverInterface
{
    /**
     * reslove conflicts between several action scoped settings
     */
    public function resolve(Collection|array $scopedSettings, string $actionClass): ScopedSetting
    {
        throw new UnresolvableScopedSettingException($scopedSettings, $actionClass);
    }
}
