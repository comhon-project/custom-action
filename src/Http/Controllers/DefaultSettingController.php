<?php

namespace Comhon\CustomAction\Http\Controllers;

use Comhon\CustomAction\Models\DefaultSetting;
use Comhon\CustomAction\Models\LocalizedSetting;
use Comhon\CustomAction\Services\ActionService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DefaultSettingController extends Controller
{
    /**
     * Display action settings.
     */
    public function show(DefaultSetting $defaultSetting)
    {
        $this->authorize('view', $defaultSetting);

        return new JsonResource($defaultSetting);
    }

    /**
     * Update action settings.
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
     */
    public function storeDefaultLocalizedSetting(Request $request, ActionService $actionService, DefaultSetting $defaultSetting)
    {
        $this->authorize('create', [LocalizedSetting::class, $defaultSetting]);

        return new JsonResource($actionService->storeLocalizedSetting($defaultSetting, $request->input()));
    }

    /**
     * Display list of localized settings.
     */
    public function listDefaultLocalizedSettings(DefaultSetting $defaultSetting)
    {
        $this->authorize('view', $defaultSetting);

        $paginator = $defaultSetting->localizedSettings()->select('id', 'locale')->paginate();

        return JsonResource::collection($paginator);
    }
}
