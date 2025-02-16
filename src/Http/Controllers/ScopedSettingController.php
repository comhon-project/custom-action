<?php

namespace Comhon\CustomAction\Http\Controllers;

use Comhon\CustomAction\Models\LocalizedSetting;
use Comhon\CustomAction\Models\ScopedSetting;
use Comhon\CustomAction\Services\ActionService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

class ScopedSettingController extends Controller
{
    /**
     * Display action scoped settings.
     */
    public function show(ScopedSetting $scopedSetting)
    {
        $this->authorize('view', $scopedSetting);

        return new JsonResource($scopedSetting);
    }

    /**
     * Update action scoped settings.
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
     */
    public function storeScopedLocalizedSetting(Request $request, ActionService $actionService, ScopedSetting $scopedSetting)
    {
        $this->authorize('create', [LocalizedSetting::class, $scopedSetting]);

        return new JsonResource($actionService->storeLocalizedSetting($scopedSetting, $request->input()));
    }

    /**
     * Display list of localized settings.
     */
    public function listScopedLocalizedSettings(ScopedSetting $scopedSetting)
    {
        $this->authorize('view', $scopedSetting);

        $paginator = $scopedSetting->localizedSettings()->select('id', 'locale')->paginate();

        return JsonResource::collection($paginator);
    }
}
