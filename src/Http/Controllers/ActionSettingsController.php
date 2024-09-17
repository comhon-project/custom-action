<?php

namespace Comhon\CustomAction\Http\Controllers;

use Comhon\CustomAction\Models\ActionSettings;
use Comhon\CustomAction\Models\EventAction;
use Comhon\CustomAction\Rules\RuleHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActionSettingsController extends Controller
{
    use ActionSettingsContainerTrait;

    /**
     * Display action settings.
     *
     * @return \Illuminate\Http\Resources\Json\JsonResource
     */
    public function show(ActionSettings $actionSetting)
    {
        $this->authorize('view', $actionSetting);

        return new JsonResource($actionSetting);
    }

    /**
     * Update action settings.
     *
     * @return \Illuminate\Http\Resources\Json\JsonResource
     */
    public function update(Request $request, ActionSettings $actionSetting)
    {
        $actionSettings = $actionSetting;
        $this->authorize('update', $actionSettings);

        $eventContext = $actionSettings->action instanceof EventAction
            ? $actionSettings->action->eventListener->getEventClass()
            : null;

        $actionClass = $actionSettings->action->getActionClass();
        $rules = RuleHelper::getSettingsRules($actionClass::getSettingsSchema($eventContext));

        $validated = $request->validate($rules);
        $actionSettings->settings = $validated['settings'] ?? [];
        $actionSettings->save();

        return new JsonResource($actionSettings->unsetRelations());
    }

    /**
     * Display list of scoped settings.
     *
     * @return \Illuminate\Http\Resources\Json\JsonResource
     */
    public function listActionScopedSettings(Request $request, ActionSettings $actionSettings)
    {
        $this->authorize('view', $actionSettings);

        $query = $actionSettings->scopedSettings();

        $name = $request->input('name');
        if ($name !== null) {
            $query->where('name', 'LIKE', "%$name%");
        }

        return new JsonResource($query->select('id', 'name')->paginate());
    }

    /**
     * Store localized settings.
     *
     * @return \Comhon\CustomAction\Resources\ActionLocalizedSettingsResource
     */
    public function storeActionLocalizedSettings(Request $request, ActionSettings $actionSettings)
    {
        return $this->storeLocalizedSettings($request, $actionSettings);
    }

    /**
     * Display list of localized settings.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function listActionLocalizedSettings(ActionSettings $actionSettings)
    {
        $this->authorize('view', $actionSettings);

        $paginator = $actionSettings->localizedSettings()->select('id', 'locale')->paginate();

        return JsonResource::collection($paginator);
    }
}
