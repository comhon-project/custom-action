<?php

namespace Comhon\CustomAction\ActionSettings;

use Comhon\CustomAction\Contracts\ScopedSettingResolverInterface;
use Comhon\CustomAction\Facades\BindingsScoper;
use Comhon\CustomAction\Models\Action;
use Comhon\CustomAction\Models\Setting;

class SettingSelector
{
    public static function select(Action $action, ?array $bindings): Setting
    {
        if ($bindings === null) {
            return $action->defaultSetting ?? throw new \Exception('missing default setting');
        }
        $possibleSettings = BindingsScoper::getScopedSetting($action, $bindings);

        if (count($possibleSettings) == 0) {
            return $action->defaultSetting ?? throw new \Exception('missing default setting');
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
