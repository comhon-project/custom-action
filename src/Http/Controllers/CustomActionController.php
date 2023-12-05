<?php

namespace Comhon\CustomAction\Http\Controllers;

use Comhon\CustomAction\Contracts\CustomActionInterface;
use Comhon\CustomAction\Contracts\CustomUniqueActionInterface;
use Comhon\CustomAction\Resolver\ModelResolverContainer;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CustomActionController extends Controller
{
    /**
     * Display a listing of actions.
     *
     * @return \Illuminate\Http\Resources\Json\JsonResource
     */
    public function listActions(ModelResolverContainer $resolver)
    {
        $actions = [
            'generic' => [],
            'unique' => [],
        ];
        foreach ($resolver->getUniqueNames(ModelResolverContainer::GENERIC_ACTION_SCOPE) as $uniqueName) {
            $translation = trans('custom-action::messages.actions.'.$uniqueName);
            $actions['generic'][] = ['type' => $uniqueName, 'name' => $translation];
        }
        foreach ($resolver->getUniqueNames(ModelResolverContainer::UNIQUE_ACTION_SCOPE) as $uniqueName) {
            $translation = trans('custom-action::messages.actions.'.$uniqueName);
            $actions['unique'][] = ['type' => $uniqueName, 'name' => $translation];
        }

        return new JsonResource($actions);
    }

    /**
     * Display action schema.
     *
     * @return \Illuminate\Http\Resources\Json\JsonResource
     */
    public function showActionSchema(ModelResolverContainer $resolver, $actionUniqueName)
    {
        if (! $resolver->isAllowedAction($actionUniqueName)) {
            throw new NotFoundHttpException('not found');
        }
        $action = app($resolver->getClass($actionUniqueName));
        $actionSchema = [
            'binding_schema' => [],
            'settings_schema' => [],
            'localized_settings_schema' => [],
            'unique' => $action instanceof CustomUniqueActionInterface,
        ];
        if ($action instanceof CustomActionInterface) {
            $actionSchema['has_target_user'] = $action->hasTargetUser();
            $actionSchema['settings_schema'] = $action->getSettingsSchema();
            $actionSchema['localized_settings_schema'] = $action->getLocalizedSettingsSchema();
            $actionSchema['binding_schema'] = $action->getBindingSchema();
        }

        return new JsonResource($actionSchema);
    }
}
