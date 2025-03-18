<?php

namespace Comhon\CustomAction\Http\Controllers;

use Comhon\CustomAction\Contracts\CustomActionInterface;
use Comhon\CustomAction\Contracts\HasBindingsInterface;
use Comhon\CustomAction\Contracts\HasTranslatableBindingsInterface;
use Comhon\CustomAction\Facades\CustomActionModelResolver;
use Comhon\CustomAction\Rules\RuleHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ActionSchemaController extends Controller
{
    /**
     * Display action schema.
     */
    public function showActionSchema(Request $request, $type)
    {
        if (! CustomActionModelResolver::isAllowedAction($type)) {
            throw new NotFoundHttpException('not found');
        }

        $this->authorize('view-schema', [CustomActionInterface::class, $type]);

        $validated = $request->validate([
            'event_context' => [
                'nullable',
                'string',
                RuleHelper::getRuleName('is').':custom-event,false,true',
            ],
        ]);

        $actionClass = CustomActionModelResolver::getClass($type);
        $eventClassContext = isset($validated['event_context'])
            ? CustomActionModelResolver::getClass($validated['event_context'])
            : null;

        $actionSchema = [
            'settings_schema' => $actionClass::getSettingsSchema($eventClassContext),
            'localized_settings_schema' => $actionClass::getLocalizedSettingsSchema($eventClassContext),
            'bindings_schema' => is_subclass_of($actionClass, HasBindingsInterface::class)
                ? $actionClass::getBindingSchema()
                : [],
            'translatable_bindings' => is_subclass_of($actionClass, HasTranslatableBindingsInterface::class)
                ? array_keys($actionClass::getTranslatableBindings())
                : [],
        ];

        return new JsonResource($actionSchema);
    }
}
