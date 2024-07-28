<?php

namespace Comhon\CustomAction\Http\Controllers;

use Comhon\CustomAction\Contracts\CustomActionInterface;
use Comhon\CustomAction\Facades\CustomActionModelResolver;
use Comhon\CustomAction\Models\ActionLocalizedSettings;
use Comhon\CustomAction\Models\ActionScopedSettings;
use Comhon\CustomAction\Models\ActionSettingsContainer;
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

        $customActionSettings = $container instanceof ActionScopedSettings ? $container->actionSettings : $container;
        $eventListener = $customActionSettings->eventAction?->eventListener;
        $eventContext = $eventListener
            ? CustomActionModelResolver::getClass($eventListener->event)
            : null;

        /** @var CustomActionInterface $customAction */
        $customAction = app(CustomActionModelResolver::getClass($customActionSettings->getAction()->type));
        $rules = RuleHelper::getSettingsRules($customAction->getLocalizedSettingsSchema($eventContext));
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
