<?php

namespace Comhon\CustomAction\Http\Controllers;

use Comhon\CustomAction\Contracts\CustomActionInterface;
use Comhon\CustomAction\Contracts\CustomUniqueActionInterface;
use Comhon\CustomAction\Models\CustomActionSettings;
use Comhon\CustomAction\Resolver\ModelResolverContainer;
use Comhon\CustomAction\Rules\RulesManager;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class ActionSettingsController extends Controller
{
    use ActionSettingsContainerTrait;

    /**
     * Display action settings.
     *
     * @return \Illuminate\Http\Resources\Json\JsonResource
     */
    public function show(ModelResolverContainer $resolver, $actionKeyOrId)
    {
        $customActionSettings = null;
        if (is_int($actionKeyOrId) || is_numeric($actionKeyOrId)) {
            $customActionSettings = CustomActionSettings::findOrFail($actionKeyOrId);
        } else {
            $actionUniqueName = $actionKeyOrId;
            if (! $resolver->isAllowedAction($actionUniqueName)) {
                throw new NotFoundHttpException('not found');
            }

            $actionClass = $resolver->getClass($actionUniqueName);
            if (! is_subclass_of($actionClass, CustomUniqueActionInterface::class)) {
                throw new UnprocessableEntityHttpException('action must be a unique action');
            }
            $customActionSettingss = CustomActionSettings::where('type', $actionUniqueName)->get();
            if ($customActionSettingss->count() == 0) {
                $customActionSettings = new CustomActionSettings();
                $customActionSettings->type = $actionUniqueName;
                $customActionSettings->settings = [];
                $customActionSettings->save();
            } else {
                if ($customActionSettingss->count() > 1) {
                    throw new \Exception("several '$actionKeyOrId' actions found");
                }
                $customActionSettings = $customActionSettingss->first();
            }
        }
        $this->authorize('view', $customActionSettings);

        return new JsonResource($customActionSettings);
    }

    /**
     * Update action settings.
     *
     * @return \Illuminate\Http\Resources\Json\JsonResource
     */
    public function update(Request $request, ModelResolverContainer $resolver, CustomActionSettings $actionSetting)
    {
        $customActionSettings = $actionSetting;
        $this->authorize('update', $customActionSettings);

        /** @var CustomActionInterface $customAction */
        $customAction = app($resolver->getClass($customActionSettings->type));
        $rules = RulesManager::getSettingsRules($customAction->getSettingsSchema(), $customAction->hasTargetUser());

        $validated = $request->validate($rules);
        $customActionSettings->settings = $validated['settings'];
        $customActionSettings->save();

        return new JsonResource($customActionSettings);
    }

    /**
     * Display list of scoped settings.
     *
     * @return \Illuminate\Http\Resources\Json\JsonResource
     */
    public function listActionScopedSettings(CustomActionSettings $customActionSettings)
    {
        $this->authorize('view', $customActionSettings);

        return new JsonResource(
            $customActionSettings->scopedSettings()->get(['id'])->pluck('id')
        );
    }

    /**
     * Store localized settings.
     *
     * @return \Comhon\CustomAction\Resources\ActionLocalizedSettingsResource
     */
    public function storeActionLocalizedSettings(
        Request $request,
        ModelResolverContainer $resolver,
        CustomActionSettings $customActionSettings
    ) {
        return $this->storeLocalizedSettings($request, $resolver, $customActionSettings);
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
