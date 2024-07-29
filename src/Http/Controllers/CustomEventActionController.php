<?php

namespace Comhon\CustomAction\Http\Controllers;

use Comhon\CustomAction\Contracts\CustomActionInterface;
use Comhon\CustomAction\Contracts\TriggerableFromEventInterface;
use Comhon\CustomAction\Facades\CustomActionModelResolver;
use Comhon\CustomAction\Models\CustomActionSettings;
use Comhon\CustomAction\Models\CustomEventAction;
use Comhon\CustomAction\Models\CustomEventListener;
use Comhon\CustomAction\Rules\RuleHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

class CustomEventActionController extends Controller
{
    /**
     * Store event listener action.
     *
     * @return \Illuminate\Http\Resources\Json\JsonResource
     */
    public function store(Request $request, CustomEventListener $eventListener)
    {
        $this->authorize('create', [CustomEventAction::class, $eventListener]);

        $validated = $this->validateStoreRequest($request, $eventListener);

        $customEventAction = new CustomEventAction;
        $customEventAction->eventListener()->associate($eventListener->id);
        $customEventAction->type = $validated['type'];
        $customEventAction->name = $validated['name'];

        DB::transaction(function () use ($customEventAction, $validated) {
            $customActionSettings = new CustomActionSettings;
            $customActionSettings->settings = $validated['settings'] ?? [];
            $customActionSettings->save();

            $customEventAction->actionSettings()->associate($customActionSettings);
            $customEventAction->save();
        });

        return new JsonResource($customEventAction);
    }

    /**
     * Show event listener action.
     */
    public function show(CustomEventAction $eventAction)
    {
        $this->authorize('view', $eventAction);

        return new JsonResource($eventAction);
    }

    /**
     * update event listener action.
     */
    public function update(Request $request, CustomEventAction $eventAction)
    {
        $this->authorize('update', $eventAction);

        $validated = $request->validate([
            'name' => 'required|string|max:63',
        ]);
        $eventAction->name = $validated['name'];
        $eventAction->save();

        return new JsonResource($eventAction);
    }

    /**
     * delete event listener action.
     */
    public function destroy(CustomEventAction $eventAction)
    {
        $this->authorize('delete', $eventAction);

        DB::transaction(function () use ($eventAction) {
            $eventAction->delete();
        });

        return response('', 204);
    }

    private function validateStoreRequest(Request $request, CustomEventListener $eventListener)
    {
        $eventClass = CustomActionModelResolver::getClass($eventListener->event);
        $allowedTypes = collect($eventClass::getAllowedActions())
            ->map(fn ($class) => CustomActionModelResolver::getUniqueName($class))
            ->filter(fn ($key) => $key !== null);

        $validated = $request->validate([
            'type' => [
                'required',
                'string',
                'in:'.$allowedTypes->implode(','),
                function (string $attribute, $type, $fail) {
                    $actionClass = CustomActionModelResolver::getClass($type);
                    $customAction = $actionClass ? app($actionClass) : null;
                    if (! $customAction instanceof CustomActionInterface) {
                        $fail("Action {$type} not found.");
                    }
                    if (! $customAction instanceof TriggerableFromEventInterface) {
                        $fail("The action {$type} is not an action triggerable from event.");
                    }
                },
            ],
            'name' => 'required|string|max:63',
        ]);

        $actionClass = CustomActionModelResolver::getClass($validated['type']);
        $customAction = app($actionClass);
        $settingsRules = RuleHelper::getSettingsRules($customAction->getSettingsSchema($eventClass));

        return [
            ...$validated,
            ...$request->validate($settingsRules),
        ];
    }
}
