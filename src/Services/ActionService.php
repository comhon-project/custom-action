<?php

namespace Comhon\CustomAction\Services;

use Comhon\CustomAction\Models\Action;
use Comhon\CustomAction\Models\ActionScopedSettings;
use Comhon\CustomAction\Models\ActionSettings;
use Comhon\CustomAction\Models\EventAction;
use Comhon\CustomAction\Rules\RuleHelper;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class ActionService
{
    public function storeDefaultSettings(Action $action, array $input): ActionSettings
    {
        if ($action->actionSettings()->exists()) {
            throw new AccessDeniedHttpException('default settings already exist');
        }
        $validated = Validator::validate($input, $this->getSettingsRules($action, false));
        $defaultSettings = new ActionSettings($validated);
        $defaultSettings->action()->associate($action);
        $defaultSettings->save();
        $defaultSettings->unsetRelation('action');

        return $defaultSettings;
    }

    public function storeScopedSettings(Action $action, array $input): ActionScopedSettings
    {
        $validated = Validator::validate($input, $this->getSettingsRules($action, true));
        $scopedSettings = new ActionScopedSettings($validated);
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
