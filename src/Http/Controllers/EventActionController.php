<?php

namespace Comhon\CustomAction\Http\Controllers;

use Comhon\CustomAction\Contracts\CustomActionInterface;
use Comhon\CustomAction\Facades\CustomActionModelResolver;
use Comhon\CustomAction\Models\ActionSettings;
use Comhon\CustomAction\Models\EventAction;
use Comhon\CustomAction\Models\EventListener;
use Comhon\CustomAction\Rules\RuleHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

class EventActionController extends Controller
{
    /**
     * Store event listener action.
     *
     * @return \Illuminate\Http\Resources\Json\JsonResource
     */
    public function store(Request $request, EventListener $eventListener)
    {
        $this->authorize('create', [EventAction::class, $eventListener]);

        $validated = $this->validateStoreRequest($request, $eventListener);

        $eventAction = new EventAction;
        $eventAction->eventListener()->associate($eventListener->id);
        $eventAction->type = $validated['type'];
        $eventAction->name = $validated['name'];

        DB::transaction(function () use ($eventAction, $validated) {
            $eventAction->save();

            $actionSettings = new ActionSettings;
            $actionSettings->settings = $validated['settings'] ?? [];
            $actionSettings->action()->associate($eventAction);
            $actionSettings->save();

            // to avoid infinite loop
            $actionSettings->unsetRelation('action');

            $eventAction->setRelation('actionSettings', $actionSettings);
        });

        return new JsonResource($eventAction);
    }

    /**
     * Show event listener action.
     */
    public function show(EventAction $eventAction)
    {
        $this->authorize('view', $eventAction);

        return new JsonResource($eventAction->load('actionSettings'));
    }

    /**
     * update event listener action.
     */
    public function update(Request $request, EventAction $eventAction)
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
    public function destroy(EventAction $eventAction)
    {
        $this->authorize('delete', $eventAction);

        DB::transaction(function () use ($eventAction) {
            $eventAction->delete();
        });

        return response('', 204);
    }

    private function validateStoreRequest(Request $request, EventListener $eventListener)
    {
        $eventClass = $eventListener->getEventClass();
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
                    if (! is_subclass_of($actionClass, CustomActionInterface::class)) {
                        $fail("Action {$type} not found.");
                    }
                },
            ],
            'name' => 'required|string|max:63',
        ]);

        $actionClass = CustomActionModelResolver::getClass($validated['type']);
        $settingsRules = RuleHelper::getSettingsRules($actionClass::getSettingsSchema($eventClass));

        return [
            ...$validated,
            ...$request->validate($settingsRules),
        ];
    }
}
