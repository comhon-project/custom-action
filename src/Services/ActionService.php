<?php

namespace Comhon\CustomAction\Services;

use Comhon\CustomAction\Models\Action;
use Comhon\CustomAction\Models\DefaultSetting;
use Comhon\CustomAction\Models\EventAction;
use Comhon\CustomAction\Models\ScopedSetting;
use Comhon\CustomAction\Rules\RuleHelper;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class ActionService
{
    public function storeDefaultSetting(Action $action, array $input): DefaultSetting
    {
        if ($action->defaultSetting()->exists()) {
            throw new AccessDeniedHttpException('default settings already exist');
        }
        $validated = Validator::validate($input, $this->getSettingsRules($action, false));
        $defaultSetting = new DefaultSetting($validated);
        $defaultSetting->action()->associate($action);
        $defaultSetting->save();
        $defaultSetting->unsetRelation('action');

        return $defaultSetting;
    }

    public function storeScopedSetting(Action $action, array $input): ScopedSetting
    {
        $validated = Validator::validate($input, $this->getSettingsRules($action, true));
        $scopedSettings = new ScopedSetting($validated);
        $scopedSettings->action()->associate($action);
        $scopedSettings->save();
        $scopedSettings->unsetRelation('action');

        return $scopedSettings;
    }

    public function getSettingsRules(Action $action, bool $scoped): array
    {
        $actionClass = $action->getActionClass();
        $eventContext = $action instanceof EventAction
            ? $action->eventListener->getEventClass()
            : null;

        $rules = RuleHelper::getSettingsRules($actionClass::getSettingsSchema($eventContext));
        if ($scoped) {
            $rules['scope'] = 'required|array';
            $rules['name'] = 'required|string|max:63';
        }

        return $rules;
    }
}
