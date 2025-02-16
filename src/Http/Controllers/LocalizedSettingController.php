<?php

namespace Comhon\CustomAction\Http\Controllers;

use Comhon\CustomAction\Models\EventAction;
use Comhon\CustomAction\Models\LocalizedSetting;
use Comhon\CustomAction\Rules\RuleHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LocalizedSettingController extends Controller
{
    /**
     * Display localized settings.
     */
    public function show(LocalizedSetting $localizedSetting)
    {
        $this->authorize('view', $localizedSetting);

        return new JsonResource($localizedSetting);
    }

    /**
     * Update localized settings.
     */
    public function update(Request $request, LocalizedSetting $localizedSetting)
    {
        $this->authorize('update', $localizedSetting);

        /** @var \Comhon\CustomAction\Models\Setting $setting */
        $setting = $localizedSetting->localizable;

        $eventContext = $setting->action instanceof EventAction
            ? $setting->action->eventListener->getEventClass()
            : null;

        $actionClass = $setting->action->getActionClass();
        $rules = RuleHelper::getSettingsRules($actionClass::getLocalizedSettingsSchema($eventContext));
        $rules['locale'] = 'string';
        $validated = $request->validate($rules);

        $localizedSetting->settings = $validated['settings'] ?? [];
        if (isset($validated['locale'])) {
            $localizedSetting->locale = $validated['locale'];
        }
        $localizedSetting->save();

        return new JsonResource($localizedSetting->unsetRelations());
    }

    /**
     * Delete localized settings.
     */
    public function destroy(LocalizedSetting $localizedSetting)
    {
        $this->authorize('delete', $localizedSetting);

        $localizedSetting->delete();

        return response('', 204);
    }
}
