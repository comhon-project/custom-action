<?php

namespace Comhon\CustomAction\Http\Controllers;

use Comhon\CustomAction\Models\Action;
use Comhon\CustomAction\Models\ActionSettings;
use Comhon\CustomAction\Services\ActionService;
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
    public function update(Request $request, ActionService $actionService, ActionSettings $actionSetting)
    {
        $actionSettings = $actionSetting;
        $this->authorize('update', $actionSettings);

        $validated = $request->validate($actionService->getSettingsRules($actionSetting->action, false));
        $actionSettings->settings = $validated['settings'];
        $actionSettings->save();

        return new JsonResource($actionSettings->unsetRelations());
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
