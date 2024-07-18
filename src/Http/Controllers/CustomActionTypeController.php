<?php

namespace Comhon\CustomAction\Http\Controllers;

use Comhon\CustomAction\Contracts\CustomActionInterface;
use Comhon\CustomAction\Facades\CustomActionModelResolver;
use Comhon\CustomAction\Rules\RuleHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CustomActionTypeController extends Controller
{
    /**
     * Display a listing of actions.
     *
     * @return \Illuminate\Http\Resources\Json\JsonResource
     */
    public function listUniqueActionTypes()
    {
        $this->authorize('view-any', CustomActionInterface::class);

        $actions = config('custom-action.unique_actions') ?? [];
        $actions = collect($actions)->map(function ($class) {
            $uniqueName = CustomActionModelResolver::getUniqueName($class);

            return [
                'type' => $uniqueName,
                'name' => trans('custom-action::messages.actions.'.$uniqueName),
            ];
        })->values();

        return new JsonResource($actions);
    }

    /**
     * Display action schema.
     *
     * @return \Illuminate\Http\Resources\Json\JsonResource
     */
    public function showActionTypeSchema(Request $request, $actionUniqueName)
    {
        if (! CustomActionModelResolver::isAllowedAction($actionUniqueName)) {
            throw new NotFoundHttpException('not found');
        }
        $action = app(CustomActionModelResolver::getClass($actionUniqueName));

        $this->authorize('view', $action);

        $validated = $request->validate([
            'event_context' => [
                'nullable',
                'string',
                RuleHelper::getRuleName('is').':custom-event,false',
            ],
        ]);
        $eventClassContext = isset($validated['event_context'])
            ? CustomActionModelResolver::getClass($validated['event_context'])
            : null;

        $actionSchema = [
            'binding_schema' => [],
            'settings_schema' => [],
            'localized_settings_schema' => [],
        ];
        if ($action instanceof CustomActionInterface) {
            $actionSchema['settings_schema'] = $action->getSettingsSchema($eventClassContext);
            $actionSchema['localized_settings_schema'] = $action->getLocalizedSettingsSchema($eventClassContext);
            $actionSchema['binding_schema'] = $action->getBindingSchema();
        }

        return new JsonResource($actionSchema);
    }
}
