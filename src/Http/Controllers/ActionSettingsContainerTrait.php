<?php

namespace Comhon\CustomAction\Http\Controllers;

use Comhon\CustomAction\Contracts\CustomActionInterface;
use Comhon\CustomAction\Models\ActionLocalizedSettings;
use Comhon\CustomAction\Models\ActionScopedSettings;
use Comhon\CustomAction\Models\ActionSettingsContainer;
use Comhon\CustomAction\Resolver\ModelResolverContainer;
use Comhon\CustomAction\Resources\ActionLocalizedSettingsResource;
use Comhon\CustomAction\Rules\RulesManager;
use Illuminate\Http\Request;

trait ActionSettingsContainerTrait
{
    /**
     * Store localized settings.
     *
     * @return \Comhon\CustomAction\Resources\ActionLocalizedSettingsResource
     */
    protected function storeLocalizedSettings(Request $request, ModelResolverContainer $resolver, ActionSettingsContainer $container)
    {
        /** @var CustomActionInterface $customAction */
        $customActionSettings = $container instanceof ActionScopedSettings ? $container->customActionSettings : $container;
        $customAction = app($resolver->getClass($customActionSettings->type));
        $rules = RulesManager::getSettingsRules($customAction->getLocalizedSettingsSchema());
        $rules['locale'] = 'required|string';
        $validated = $request->validate($rules);

        $localizedSettings = new ActionLocalizedSettings();
        $localizedSettings->settings = $validated['settings'];
        $localizedSettings->locale = $validated['locale'];
        $localizedSettings->localizable()->associate($container);
        $localizedSettings->save();

        return new ActionLocalizedSettingsResource($localizedSettings);
    }
}
