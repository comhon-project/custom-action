<?php

namespace Comhon\CustomAction\Http\Controllers;

use Comhon\CustomAction\Contracts\CustomActionInterface;
use Comhon\CustomAction\Contracts\HasContextInterface;
use Comhon\CustomAction\Contracts\HasContextKeysIgnoredForScopedSettingInterface;
use Comhon\CustomAction\Contracts\HasTranslatableContextInterface;
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
            'context_schema' => is_subclass_of($actionClass, HasContextInterface::class)
                ? $actionClass::getContextSchema()
                : [],
            'translatable_context' => is_subclass_of($actionClass, HasTranslatableContextInterface::class)
                ? array_keys($actionClass::getTranslatableContext())
                : [],
            'context_keys_ignored_for_scoped_setting' => is_subclass_of($actionClass, HasContextKeysIgnoredForScopedSettingInterface::class)
                ? $actionClass::getContextKeysIgnoredForScopedSetting()
                : [],
        ];

        return new JsonResource($actionSchema);
    }
}
