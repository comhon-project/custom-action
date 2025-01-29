<?php

namespace Comhon\CustomAction\Http\Controllers;

use Comhon\CustomAction\Models\Action;
use Comhon\CustomAction\Models\ActionScopedSettings;
use Comhon\CustomAction\Services\ActionService;
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
     * Update action scoped settings.
     *
     * @return \Illuminate\Http\Resources\Json\JsonResource
     */
    public function update(Request $request, ActionService $actionService, ActionScopedSettings $scopedSetting)
    {
        $scopedSettings = $scopedSetting;
        $this->authorize('update', $scopedSettings);

        $validated = $request->validate($actionService->getSettingsRules($scopedSettings->action, true));
        $scopedSettings->fill($validated);
        $scopedSettings->save();

        return new JsonResource($scopedSettings->unsetRelations());
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
