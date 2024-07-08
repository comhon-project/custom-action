<?php

namespace Comhon\CustomAction\Http\Controllers;

use Comhon\CustomAction\Contracts\CustomActionInterface;
use Comhon\CustomAction\Facades\CustomActionModelResolver;
use Comhon\CustomAction\Models\ActionScopedSettings;
use Comhon\CustomAction\Models\CustomActionSettings;
use Comhon\CustomAction\Rules\RuleHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
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
        $this->authorize('view', $scopedSetting);

        return new JsonResource($scopedSetting);
    }

    /**
     * Store action scoped settings.
     *
     * @return \Illuminate\Http\Resources\Json\JsonResource
     */
    public function store(Request $request, CustomActionSettings $customActionSettings)
    {
        $this->authorize('create', [ActionScopedSettings::class, $customActionSettings]);
        $eventListener = $customActionSettings->eventListener();
        $eventContext = $eventListener
            ? CustomActionModelResolver::getClass($eventListener->event)
            : null;

        /** @var CustomActionInterface $customAction */
        $customAction = app(CustomActionModelResolver::getClass($customActionSettings->type));
        $rules = RuleHelper::getSettingsRules($customAction->getSettingsSchema($eventContext));
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
    public function update(Request $request, ActionScopedSettings $scopedSetting)
    {
        $scopedSettings = $scopedSetting;
        $this->authorize('update', $scopedSettings);

        $eventListener = $scopedSettings->customActionSettings->eventListener();
        $eventContext = $eventListener
            ? CustomActionModelResolver::getClass($eventListener->event)
            : null;

        $customAction = app(CustomActionModelResolver::getClass($scopedSettings->customActionSettings->type));
        $rules = RuleHelper::getSettingsRules($customAction->getSettingsSchema($eventContext));
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
        $this->authorize('delete', $scopedSettings);

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
    public function storeScopedSettingsLocalizedSettings(Request $request, ActionScopedSettings $scopedSettings)
    {
        return $this->storeLocalizedSettings($request, $scopedSettings);
    }

    /**
     * Display list of localized settings.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function listScopedSettingsLocalizedSettings(ActionScopedSettings $scopedSettings)
    {
        $this->authorize('view', $scopedSettings);

        $paginator = $scopedSettings->localizedSettings()->select('id', 'locale')->paginate();

        return JsonResource::collection($paginator);
    }
}
