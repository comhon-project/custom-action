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
        $eventListener = $customActionSettings->eventAction?->eventListener;
        $eventContext = $eventListener
            ? CustomActionModelResolver::getClass($eventListener->event)
            : null;

        $validated = $this->validateRequest($request, $customActionSettings->getAction()->type, $eventContext);

        $scopedSettings = new ActionScopedSettings($validated);
        $scopedSettings->actionSettings()->associate($customActionSettings->id);
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

        $eventListener = $scopedSettings->actionSettings->eventAction?->eventListener;
        $eventContext = $eventListener
            ? CustomActionModelResolver::getClass($eventListener->event)
            : null;

        $validated = $this->validateRequest($request, $scopedSettings->actionSettings->getAction()->type, $eventContext);
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

    private function validateRequest(Request $request, string $type, ?string $eventContext)
    {
        /** @var CustomActionInterface $customAction */
        $customAction = app(CustomActionModelResolver::getClass($type));
        $rules = RuleHelper::getSettingsRules($customAction->getSettingsSchema($eventContext));
        $rules['scope'] = 'required|array';
        $rules['name'] = 'required|string|max:63';

        return $request->validate($rules);
    }
}
