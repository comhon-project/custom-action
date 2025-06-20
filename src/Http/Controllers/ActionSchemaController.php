<?php

namespace Comhon\CustomAction\Http\Controllers;

use Comhon\CustomAction\Contracts\CustomActionInterface;
use Comhon\CustomAction\Contracts\CustomEventInterface;
use Comhon\CustomAction\Contracts\ExposeContextInterface;
use Comhon\CustomAction\Contracts\FakableInterface;
use Comhon\CustomAction\Contracts\HasContextKeysIgnoredForScopedSettingInterface;
use Comhon\CustomAction\Contracts\HasFakeStateInterface;
use Comhon\CustomAction\Contracts\HasTranslatableContextInterface;
use Comhon\CustomAction\Contracts\SimulatableInterface;
use Comhon\CustomAction\Facades\CustomActionModelResolver;
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
                function (string $attribute, mixed $value, \Closure $fail) {
                    $class = CustomActionModelResolver::getClass($value);
                    if (! $class || ! is_subclass_of($class, CustomEventInterface::class)) {
                        $fail("The {$attribute} is not subclass of custom-event.");
                    }
                },
            ],
        ]);

        $actionClass = CustomActionModelResolver::getClass($type);
        $eventClassContext = isset($validated['event_context'])
            ? CustomActionModelResolver::getClass($validated['event_context'])
            : null;

        $contextClass = $eventClassContext ?? $actionClass;
        $simulatable = is_subclass_of($contextClass, FakableInterface::class)
            && is_subclass_of($actionClass, SimulatableInterface::class);

        $actionSchema = [
            'settings_schema' => $actionClass::getSettingsSchema($eventClassContext),
            'localized_settings_schema' => $actionClass::getLocalizedSettingsSchema($eventClassContext),
            'context_schema' => is_subclass_of($actionClass, ExposeContextInterface::class)
                ? $actionClass::getContextSchema()
                : [],
            'translatable_context' => is_subclass_of($actionClass, HasTranslatableContextInterface::class)
                ? array_keys($actionClass::getTranslatableContext())
                : [],
            'context_keys_ignored_for_scoped_setting' => is_subclass_of($actionClass, HasContextKeysIgnoredForScopedSettingInterface::class)
                ? $actionClass::getContextKeysIgnoredForScopedSetting()
                : [],
            'simulatable' => $simulatable,
            'fake_state_schema' => $simulatable && is_subclass_of($contextClass, HasFakeStateInterface::class)
                ? $contextClass::getFakeStateSchema()
                : null,
        ];

        return new JsonResource($actionSchema);
    }
}
