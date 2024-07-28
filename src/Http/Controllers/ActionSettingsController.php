<?php

namespace Comhon\CustomAction\Http\Controllers;

use Comhon\CustomAction\Contracts\CustomActionInterface;
use Comhon\CustomAction\Facades\CustomActionModelResolver;
use Comhon\CustomAction\Models\CustomActionSettings;
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
    public function show(CustomActionSettings $actionSetting)
    {
        $this->authorize('view', $actionSetting);

        return new JsonResource($actionSetting);
    }

    /**
     * Update action settings.
     *
     * @return \Illuminate\Http\Resources\Json\JsonResource
     */
    public function update(Request $request, CustomActionSettings $actionSetting)
    {
        $customActionSettings = $actionSetting;
        $this->authorize('update', $customActionSettings);

        $eventListener = $customActionSettings->eventAction?->eventListener;
        $eventContext = $eventListener
            ? CustomActionModelResolver::getClass($eventListener->event)
            : null;

        /** @var CustomActionInterface $customAction */
        $customAction = app(CustomActionModelResolver::getClass($customActionSettings->getAction()->type));
        $rules = RuleHelper::getSettingsRules($customAction->getSettingsSchema($eventContext));

        $validated = $request->validate($rules);
        $customActionSettings->settings = $validated['settings'] ?? [];
        $customActionSettings->save();

        return new JsonResource($customActionSettings->unsetRelations());
    }

    /**
     * Display list of scoped settings.
     *
     * @return \Illuminate\Http\Resources\Json\JsonResource
     */
    public function listActionScopedSettings(Request $request, CustomActionSettings $customActionSettings)
    {
        $this->authorize('view', $customActionSettings);

        $query = $customActionSettings->scopedSettings();

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
    public function storeActionLocalizedSettings(Request $request, CustomActionSettings $customActionSettings)
    {
        return $this->storeLocalizedSettings($request, $customActionSettings);
    }

    /**
     * Display list of localized settings.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function listActionLocalizedSettings(CustomActionSettings $customActionSettings)
    {
        $this->authorize('view', $customActionSettings);

        $paginator = $customActionSettings->localizedSettings()->select('id', 'locale')->paginate();

        return JsonResource::collection($paginator);
    }
}
