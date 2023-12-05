<?php

namespace Comhon\CustomAction\Http\Controllers;

use Comhon\CustomAction\Contracts\CustomActionInterface;
use Comhon\CustomAction\Models\ActionScopedSettings;
use Comhon\CustomAction\Models\CustomActionSettings;
use Comhon\CustomAction\Resolver\ModelResolverContainer;
use Comhon\CustomAction\Rules\RulesManager;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class ActionScopedSettingsController extends Controller
{
    use ActionSettingsContainerTrait;

    /**
     * Display action scoped settings.
     *
     * @return \Illuminate\Http\Resources\Json\JsonResource
     */
    public function show(ActionScopedSettings $scopedSetting)
    {
        return new JsonResource($scopedSetting);
    }

    /**
     * Store action scoped settings.
     *
     * @return \Illuminate\Http\Resources\Json\JsonResource
     */
    public function store(Request $request, ModelResolverContainer $resolver, CustomActionSettings $customActionSettings)
    {
        /** @var CustomActionInterface $customAction */
        $customAction = app($resolver->getClass($customActionSettings->type));
        $rules = RulesManager::getSettingsRules($customAction->getSettingsSchema(), $customAction->hasTargetUser());
        $rules['scope'] = 'array|required';
        $validated = $request->validate($rules);

        $scopedSettings = new ActionScopedSettings();
        $scopedSettings->settings = $validated['settings'] ?? [];
        $scopedSettings->scope = $validated['scope'];
        $scopedSettings->custom_action_settings_id = $customActionSettings->id;
        $scopedSettings->save();

        return new JsonResource($scopedSettings);
    }

    /**
     * Update action scoped settings.
     *
     * @return \Illuminate\Http\Resources\Json\JsonResource
     */
    public function update(Request $request, ModelResolverContainer $resolver, ActionScopedSettings $scopedSetting)
    {
        $scopedSettings = $scopedSetting;
        $customAction = app($resolver->getClass($scopedSettings->customActionSettings->type));
        $rules = RulesManager::getSettingsRules($customAction->getSettingsSchema(), $customAction->hasTargetUser());
        $rules['scope'] = 'array|required';
        $validated = $request->validate($rules);

        $scopedSettings->settings = $validated['settings'] ?? [];
        if (isset($validated['scope'])) {
            $scopedSettings->scope = $validated['scope'];
        }
        $scopedSettings->save();

        return new JsonResource($scopedSettings);
    }

    /**
     * Delete action scoped settings.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(ActionScopedSettings $scopedSetting)
    {
        $scopedSettings = $scopedSetting;
        DB::transaction(function () use ($scopedSettings) {
            $scopedSettings->delete();
        });

        return response('', 204);
    }

    /**
     * Store localized settings.
     *
     * @return \Comhon\CustomAction\Resources\ActionLocalizedSettingsResource
     */
    public function storeScopedSettingsLocalizedSettings(
        Request $request,
        ModelResolverContainer $resolver,
        ActionScopedSettings $scopedSettings
    ) {
        return $this->storeLocalizedSettings($request, $resolver, $scopedSettings);
    }

    /**
     * Display list of localized settings.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function listScopedSettingsLocalizedSettings(ActionScopedSettings $scopedSettings)
    {
        $paginator = $scopedSettings->localizedSettings()->select('id', 'locale')->paginate();

        return JsonResource::collection($paginator);
    }
}
