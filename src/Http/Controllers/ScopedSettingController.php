<?php

namespace Comhon\CustomAction\Http\Controllers;

use Comhon\CustomAction\Models\ScopedSetting;
use Comhon\CustomAction\Services\ActionService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

class ScopedSettingController extends Controller
{
    use SettingTrait;

    /**
     * Display action scoped settings.
     *
     * @return \Illuminate\Http\Resources\Json\JsonResource
     */
    public function show(ScopedSetting $scopedSetting)
    {
        $this->authorize('view', $scopedSetting);

        return new JsonResource($scopedSetting);
    }

    /**
     * Update action scoped settings.
     *
     * @return \Illuminate\Http\Resources\Json\JsonResource
     */
    public function update(Request $request, ActionService $actionService, ScopedSetting $scopedSetting)
    {
        $this->authorize('update', $scopedSetting);

        $validated = $request->validate($actionService->getSettingsRules($scopedSetting->action, true));
        $scopedSetting->fill($validated);
        $scopedSetting->save();

        return new JsonResource($scopedSetting->unsetRelations());
    }

    /**
     * Delete action scoped settings.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(ScopedSetting $scopedSetting)
    {
        $this->authorize('delete', $scopedSetting);

        DB::transaction(function () use ($scopedSetting) {
            $scopedSetting->delete();
        });

        return response('', 204);
    }

    /**
     * Store localized settings.
     *
     * @return \Comhon\CustomAction\Resources\LocalizedSettingResource
     */
    public function storeScopedLocalizedSetting(Request $request, ScopedSetting $scopedSetting)
    {
        return $this->storeLocalizedSetting($request, $scopedSetting);
    }

    /**
     * Display list of localized settings.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function listScopedLocalizedSettings(ScopedSetting $scopedSetting)
    {
        $this->authorize('view', $scopedSetting);

        $paginator = $scopedSetting->localizedSettings()->select('id', 'locale')->paginate();

        return JsonResource::collection($paginator);
    }
}
