<?php

namespace Comhon\CustomAction\Http\Controllers;

use Comhon\CustomAction\Models\DefaultSetting;
use Comhon\CustomAction\Services\ActionService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DefaultSettingController extends Controller
{
    use SettingTrait;

    /**
     * Display action settings.
     *
     * @return \Illuminate\Http\Resources\Json\JsonResource
     */
    public function show(DefaultSetting $defaultSetting)
    {
        $this->authorize('view', $defaultSetting);

        return new JsonResource($defaultSetting);
    }

    /**
     * Update action settings.
     *
     * @return \Illuminate\Http\Resources\Json\JsonResource
     */
    public function update(Request $request, ActionService $actionService, DefaultSetting $defaultSetting)
    {
        $this->authorize('update', $defaultSetting);

        $validated = $request->validate($actionService->getSettingsRules($defaultSetting->action, false));
        $defaultSetting->settings = $validated['settings'];
        $defaultSetting->save();

        return new JsonResource($defaultSetting->unsetRelations());
    }

    /**
     * Store localized settings.
     *
     * @return \Comhon\CustomAction\Resources\LocalizedSettingResource
     */
    public function storeDefaultLocalizedSetting(Request $request, DefaultSetting $defaultSetting)
    {
        return $this->storeLocalizedSetting($request, $defaultSetting);
    }

    /**
     * Display list of localized settings.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function listDefaultLocalizedSettings(DefaultSetting $defaultSetting)
    {
        $this->authorize('view', $defaultSetting);

        $paginator = $defaultSetting->localizedSettings()->select('id', 'locale')->paginate();

        return JsonResource::collection($paginator);
    }
}
