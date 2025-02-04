<?php

namespace Comhon\CustomAction\ActionSettings;

use Comhon\CustomAction\Contracts\ScopedSettingResolverInterface;
use Comhon\CustomAction\Exceptions\MissingSettingException;
use Comhon\CustomAction\Facades\BindingsScoper;
use Comhon\CustomAction\Models\Action;
use Comhon\CustomAction\Models\Setting;

class SettingSelector
{
    public static function select(Action $action, ?array $bindings): Setting
    {
        if ($bindings === null) {
            return $action->defaultSetting ?? throw new MissingSettingException($action, true);
        }
        $possibleSettings = BindingsScoper::getScopedSettings($action, $bindings);

        if (count($possibleSettings) == 0) {
            return $action->defaultSetting ?? throw new MissingSettingException($action, false);
        }
        if (count($possibleSettings) == 1) {
            foreach ($possibleSettings as $setting) {
                return $setting;
            }
        }

        $resolver = app(ScopedSettingResolverInterface::class);

        return $resolver->resolve($possibleSettings, $action->getActionClass());
    }
}
