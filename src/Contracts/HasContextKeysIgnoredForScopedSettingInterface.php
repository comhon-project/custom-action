<?php

namespace Comhon\CustomAction\Contracts;

interface HasContextKeysIgnoredForScopedSettingInterface
{
    /**
     * Get context keys that should be ignored when defining scoped setting
     */
    public static function getContextKeysIgnoredForScopedSetting(): array;
}
