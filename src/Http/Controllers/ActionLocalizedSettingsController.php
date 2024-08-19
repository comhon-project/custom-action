<?php

namespace Comhon\CustomAction\Http\Controllers;

use Comhon\CustomAction\Facades\CustomActionModelResolver;
use Comhon\CustomAction\Models\ActionLocalizedSettings;
use Comhon\CustomAction\Models\ActionScopedSettings;
use Comhon\CustomAction\Resources\ActionLocalizedSettingsResource;
use Comhon\CustomAction\Rules\RuleHelper;
use Illuminate\Http\Request;

class ActionLocalizedSettingsController extends Controller
{
    /**
     * Display localized settings.
     *
     * @return \Comhon\CustomAction\Resources\ActionLocalizedSettingsResource
     */
    public function show(ActionLocalizedSettings $localizedSetting)
    {
        $this->authorize('view', $localizedSetting);

        return new ActionLocalizedSettingsResource($localizedSetting);
    }

    /**
     * Update localized settings.
     *
     * @return \Comhon\CustomAction\Resources\ActionLocalizedSettingsResource
     */
    public function update(Request $request, ActionLocalizedSettings $localizedSetting)
    {
        $localizedSettings = $localizedSetting;
        $this->authorize('update', $localizedSettings);

        $container = $localizedSettings->localizable;
        $actionSettings = $container instanceof ActionScopedSettings ? $container->actionSettings : $container;

        $eventListener = $actionSettings->eventAction?->eventListener;
        $eventContext = $eventListener
            ? CustomActionModelResolver::getClass($eventListener->event)
            : null;

        $actionClass = CustomActionModelResolver::getClass($actionSettings->getAction()->type);
        $rules = RuleHelper::getSettingsRules($actionClass::getLocalizedSettingsSchema($eventContext));
        $rules['locale'] = 'string';
        $validated = $request->validate($rules);

        $localizedSettings->settings = $validated['settings'] ?? [];
        if (isset($validated['locale'])) {
            $localizedSettings->locale = $validated['locale'];
        }
        $localizedSettings->save();

        return new ActionLocalizedSettingsResource($localizedSettings->unsetRelations());
    }

    /**
     * Delete localized settings.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(ActionLocalizedSettings $localizedSetting)
    {
        $this->authorize('delete', $localizedSetting);

        $localizedSettings = $localizedSetting;
        $localizedSettings->delete();

        return response('', 204);
    }
}
