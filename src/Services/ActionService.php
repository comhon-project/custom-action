<?php

namespace Comhon\CustomAction\Services;

use Comhon\CustomAction\Contracts\SimulatableInterface;
use Comhon\CustomAction\Exceptions\SimulateActionException;
use Comhon\CustomAction\Facades\CustomActionModelResolver;
use Comhon\CustomAction\Models\Action;
use Comhon\CustomAction\Models\DefaultSetting;
use Comhon\CustomAction\Models\EventAction;
use Comhon\CustomAction\Models\LocalizedSetting;
use Comhon\CustomAction\Models\ScopedSetting;
use Comhon\CustomAction\Models\Setting;
use Comhon\CustomAction\Rules\RuleHelper;
use Illuminate\Support\Facades\App;
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
        $validated['settings'] ??= [];
        $defaultSetting = new DefaultSetting($validated);
        $defaultSetting->action()->associate($action);
        $defaultSetting->save();
        $defaultSetting->unsetRelation('action');

        return $defaultSetting;
    }

    public function storeScopedSetting(Action $action, array $input): ScopedSetting
    {
        $validated = Validator::validate($input, $this->getSettingsRules($action, true));
        $validated['settings'] ??= [];
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

    /**
     * Store localized settings.
     */
    public function storeLocalizedSetting(Setting $setting, array $inputs): LocalizedSetting
    {
        $rules = $this->getLocalizedSettingsRules($setting->action);
        $validated = Validator::validate($inputs, $rules);

        $localizedSetting = new LocalizedSetting;
        $localizedSetting->settings = $validated['settings'] ?? [];
        $localizedSetting->locale = $validated['locale'];
        $localizedSetting->localizable()->associate($setting);
        $localizedSetting->save();

        return $localizedSetting->unsetRelations();
    }

    /**
     * Store localized settings.
     */
    public function getLocalizedSettingsRules(Action $action, $prefix = 'settings'): array
    {
        $actionClass = $action->getActionClass();
        $eventContext = $action instanceof EventAction
            ? $action->eventListener->getEventClass()
            : null;

        $actionClass = $action->getActionClass();
        $rules = RuleHelper::getSettingsRules($actionClass::getLocalizedSettingsSchema($eventContext), $prefix);
        $rules['locale'] = 'required|string';

        return $rules;
    }

    public function simulate(Action $action, array $inputs)
    {
        $validated = Validator::validate($inputs, [
            ...(isset($inputs['settings']) ? $this->getSettingsRules($action, false) : []),
            ...(isset($inputs['localized_settings']) ? $this->getLocalizedSettingsRules($action, 'localized_settings') : []),

            // override 'required' rules
            'locale' => 'nullable|string',
        ]);

        $validated['settings'] ??= is_array($inputs['settings'] ?? null) ? [] : null;
        $validated['localized_settings'] ??= is_array($inputs['localized_settings'] ?? null) ? [] : null;

        $setting = $validated['settings'] !== null ? new DefaultSetting(['settings' => $validated['settings']]) : null;
        $localizedSetting = $validated['localized_settings'] !== null ?
            (new LocalizedSetting)->forceFill([
                'settings' => $validated['localized_settings'],
                'locale' => $validated['locale'] ?? App::getLocale(),
            ])
            : null;

        $customActionClass = CustomActionModelResolver::getClass($action->type);
        $customAction = $customActionClass::buildFakeInstance($action, $setting, $localizedSetting);

        if (! $customAction instanceof SimulatableInterface) {
            throw new SimulateActionException("cannot simulate action {$action->type}");
        }

        return $customAction->simulate();
    }
}
