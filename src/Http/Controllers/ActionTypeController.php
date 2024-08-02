<?php

namespace Comhon\CustomAction\Http\Controllers;

use Comhon\CustomAction\Contracts\CustomActionInterface;
use Comhon\CustomAction\Contracts\HasBindingsInterface;
use Comhon\CustomAction\Facades\CustomActionModelResolver;
use Comhon\CustomAction\Rules\RuleHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ActionTypeController extends Controller
{
    /**
     * Display a listing of actions.
     *
     * @return \Illuminate\Http\Resources\Json\JsonResource
     */
    public function listManualActionTypes()
    {
        $this->authorize('view-any', CustomActionInterface::class);

        $actions = config('custom-action.manual_actions') ?? [];
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
    public function showActionTypeSchema(Request $request, $actionType)
    {
        if (! CustomActionModelResolver::isAllowedAction($actionType)) {
            throw new NotFoundHttpException('not found');
        }
        /** @var CustomActionInterface $action */
        $action = app(CustomActionModelResolver::getClass($actionType));

        $this->authorize('view', $action);

        $validated = $request->validate([
            'event_context' => [
                'nullable',
                'string',
                RuleHelper::getRuleName('is').':custom-event,false,true',
            ],
        ]);
        $eventClassContext = isset($validated['event_context'])
            ? CustomActionModelResolver::getClass($validated['event_context'])
            : null;

        $actionSchema = [
            'settings_schema' => $action->getSettingsSchema($eventClassContext),
            'localized_settings_schema' => $action->getLocalizedSettingsSchema($eventClassContext),
            'binding_schema' => $action instanceof HasBindingsInterface
                ? $action->getBindingSchema()
                : [],
        ];

        return new JsonResource($actionSchema);
    }
}
