<?php

namespace Comhon\CustomAction\Http\Controllers;

use Comhon\CustomAction\Models\ActionLocalizedSettings;
use Comhon\CustomAction\Models\ActionScopedSettings;
use Comhon\CustomAction\Models\ActionSettingsContainer;
use Comhon\CustomAction\Models\EventAction;
use Comhon\CustomAction\Resources\ActionLocalizedSettingsResource;
use Comhon\CustomAction\Rules\RuleHelper;
use Illuminate\Http\Request;

trait ActionSettingsContainerTrait
{
    /**
     * Store localized settings.
     *
     * @return \Comhon\CustomAction\Resources\ActionLocalizedSettingsResource
     */
    protected function storeLocalizedSettings(Request $request, ActionSettingsContainer $container)
    {
        $this->authorize('create', [ActionLocalizedSettings::class, $container]);

        /** @var \Comhon\CustomAction\Models\ActionSettings $actionSettings */
        $actionSettings = $container instanceof ActionScopedSettings ? $container->actionSettings : $container;

        $eventContext = $actionSettings->action instanceof EventAction
            ? $actionSettings->action->eventListener->getEventClass()
            : null;

        $actionClass = $actionSettings->action->getActionClass();
        $rules = RuleHelper::getSettingsRules($actionClass::getLocalizedSettingsSchema($eventContext));
        $rules['locale'] = 'required|string';
        $validated = $request->validate($rules);

        $localizedSettings = new ActionLocalizedSettings;
        $localizedSettings->settings = $validated['settings'] ?? [];
        $localizedSettings->locale = $validated['locale'];
        $localizedSettings->localizable()->associate($container);
        $localizedSettings->save();

        return new ActionLocalizedSettingsResource($localizedSettings->unsetRelations());
    }
}
