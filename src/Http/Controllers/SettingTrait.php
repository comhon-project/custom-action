<?php

namespace Comhon\CustomAction\Http\Controllers;

use Comhon\CustomAction\Models\EventAction;
use Comhon\CustomAction\Models\LocalizedSetting;
use Comhon\CustomAction\Models\Setting;
use Comhon\CustomAction\Resources\LocalizedSettingResource;
use Comhon\CustomAction\Rules\RuleHelper;
use Illuminate\Http\Request;

trait SettingTrait
{
    /**
     * Store localized settings.
     *
     * @return \Comhon\CustomAction\Resources\LocalizedSettingResource
     */
    protected function storeLocalizedSetting(Request $request, Setting $setting)
    {
        $this->authorize('create', [LocalizedSetting::class, $setting]);

        $eventContext = $setting->action instanceof EventAction
            ? $setting->action->eventListener->getEventClass()
            : null;

        $actionClass = $setting->action->getActionClass();
        $rules = RuleHelper::getSettingsRules($actionClass::getLocalizedSettingsSchema($eventContext));
        $rules['locale'] = 'required|string';
        $validated = $request->validate($rules);

        $localizedSetting = new LocalizedSetting;
        $localizedSetting->settings = $validated['settings'] ?? [];
        $localizedSetting->locale = $validated['locale'];
        $localizedSetting->localizable()->associate($setting);
        $localizedSetting->save();

        return new LocalizedSettingResource($localizedSetting->unsetRelations());
    }
}
