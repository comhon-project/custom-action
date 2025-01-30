<?php

namespace Comhon\CustomAction\ActionSettings;

use Comhon\CustomAction\Contracts\ScopedSettingResolverInterface;
use Comhon\CustomAction\Exceptions\UnresolvableScopedSettingException;
use Comhon\CustomAction\Models\ScopedSetting;

class ScopedSettingResolver implements ScopedSettingResolverInterface
{
    /**
     * reslove conflicts between several action scoped settings
     */
    public function resolve(array $scopedSettings, string $actionClass): ScopedSetting
    {
        throw new UnresolvableScopedSettingException(
            'cannot resolve conflict between several action scoped settings'
        );
    }
}
