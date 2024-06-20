<?php

namespace Comhon\CustomAction\Http\Controllers;

use Comhon\CustomAction\Models\ActionLocalizedSettings;
use Comhon\CustomAction\Models\ActionScopedSettings;
use Comhon\CustomAction\Resolver\ModelResolverContainer;
use Comhon\CustomAction\Resources\ActionLocalizedSettingsResource;
use Comhon\CustomAction\Rules\RulesManager;
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
    public function update(Request $request, ModelResolverContainer $resolver, ActionLocalizedSettings $localizedSetting)
    {
        $this->authorize('update', $localizedSetting);

        $localizedSettings = $localizedSetting;
        $container = $localizedSettings->localizable;
        $customActionSettings = $container instanceof ActionScopedSettings ? $container->customActionSettings : $container;
        $customAction = app($resolver->getClass($customActionSettings->type));
        $rules = RulesManager::getSettingsRules($customAction->getLocalizedSettingsSchema());
        $rules['locale'] = 'string';
        $validated = $request->validate($rules);

        $localizedSettings->settings = $validated['settings'];
        if (isset($validated['locale'])) {
            $localizedSettings->locale = $validated['locale'];
        }
        $localizedSettings->save();

        return new ActionLocalizedSettingsResource($localizedSettings);
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
