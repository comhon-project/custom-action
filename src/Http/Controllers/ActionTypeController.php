<?php

namespace Comhon\CustomAction\Http\Controllers;

use Comhon\CustomAction\Catalogs\ManualActionTypeCatalog;
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
    public function listManualActionTypes(ManualActionTypeCatalog $catalog)
    {
        $this->authorize('view-any', CustomActionInterface::class);

        return new JsonResource($catalog->get());
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
        $actionClass = CustomActionModelResolver::getClass($actionType);

        $this->authorize('view', [CustomActionInterface::class, $actionClass]);

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
            'settings_schema' => $actionClass::getSettingsSchema($eventClassContext),
            'localized_settings_schema' => $actionClass::getLocalizedSettingsSchema($eventClassContext),
            'binding_schema' => is_subclass_of($actionClass, HasBindingsInterface::class)
                ? $actionClass::getBindingSchema()
                : [],
        ];

        return new JsonResource($actionSchema);
    }
}
