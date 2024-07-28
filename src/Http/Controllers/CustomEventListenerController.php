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
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CustomEventListenerController extends Controller
{
    /**
     * Store event listener.
     *
     * @return \Illuminate\Http\Resources\Json\JsonResource
     */
    public function store(Request $request, $eventUniqueName)
    {
        if (! CustomActionModelResolver::isAllowedEvent($eventUniqueName)) {
            throw new NotFoundHttpException('not found');
        }
        $eventClass = CustomActionModelResolver::getClass($eventUniqueName);
        $this->authorize('create', [CustomEventListener::class, $eventClass]);

        $validated = $this->validateCustomEventListenerRequest($request);

        $eventListener = new CustomEventListener($validated);
        $eventListener->event = $eventUniqueName;
        $eventListener->save();

        return new JsonResource($eventListener);
    }

    /**
     * Update event listener.
     *
     * @return \Illuminate\Http\Resources\Json\JsonResource
     */
    public function update(Request $request, CustomEventListener $eventListener)
    {
        $this->authorize('update', $eventListener);

        $validated = $this->validateCustomEventListenerRequest($request);
        $eventListener->fill($validated);
        $eventListener->save();

        return new JsonResource($eventListener);
    }

    /**
     * Delete event listener.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(CustomEventListener $eventListener)
    {
        $this->authorize('delete', $eventListener);

        DB::transaction(function () use ($eventListener) {
            $eventListener->delete();
        });

        return response('', 204);
    }

    /**
     * Display event listener actions.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function listEventListenerActions(CustomEventListener $eventListener)
    {
        $this->authorize('view', $eventListener);

        return JsonResource::collection($eventListener->eventActions()->paginate());
    }

    /**
     * Store event listener action.
     *
     * @return \Illuminate\Http\Resources\Json\JsonResource
     */
    public function storeEventListenerAction(Request $request, CustomEventListener $eventListener)
    {
        $this->authorize('create-action', $eventListener);

        $validated = $this->validateType($request, $eventListener);

        return new JsonResource($this->createAndAttachAction(
            $request,
            $eventListener,
            $validated['type'],
        ));
    }

    /**
     * Store event listener action.
     */
    private function createAndAttachAction(
        Request $request,
        CustomEventListener $eventListener,
        string $type,
    ) {
        $validated = $this->validateActionSettings($request, $type, $eventListener);

        $customEventAction = new CustomEventAction;
        $customEventAction->eventListener()->associate($eventListener->id);
        $customEventAction->type = $type;
        $customEventAction->name = $validated['name'];

        DB::transaction(function () use ($customEventAction, $validated) {
            $customActionSettings = new CustomActionSettings;
            $customActionSettings->settings = $validated['settings'] ?? [];
            $customActionSettings->save();

            $customEventAction->actionSettings()->associate($customActionSettings);
            $customEventAction->save();
        });

        return $customEventAction;
    }

    private function validateType(Request $request, CustomEventListener $eventListener)
    {
        $eventClass = CustomActionModelResolver::getClass($eventListener->event);
        $allowedTypes = collect($eventClass::getAllowedActions())
            ->map(fn ($class) => CustomActionModelResolver::getUniqueName($class))
            ->filter(fn ($key) => $key !== null);

        return $request->validate([
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
        ]);
    }

    private function validateActionSettings(Request $request, string $type, CustomEventListener $customEventListener)
    {
        $eventClass = CustomActionModelResolver::getClass($customEventListener->event);
        $actionClass = CustomActionModelResolver::getClass($type);
        $customAction = app($actionClass);
        $rules = RuleHelper::getSettingsRules($customAction->getSettingsSchema($eventClass));
        $rules['name'] = 'required|string|max:63';

        return $request->validate($rules);
    }

    /**
     * update event listener action.
     */
    public function updateEventListenerAction(Request $request, CustomEventAction $eventAction)
    {
        $this->authorize('update-action', [CustomEventListener::class, $eventAction]);

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
    public function deleteEventListenerAction(CustomEventAction $eventAction)
    {
        $this->authorize('delete-action', [CustomEventListener::class, $eventAction]);

        DB::transaction(function () use ($eventAction) {
            $eventAction->delete();
        });

        return response('', 204);
    }

    private function validateCustomEventListenerRequest(Request $request)
    {
        return $request->validate([
            'scope' => 'array|nullable',
            'name' => 'required|string|max:63',
        ]);
    }
}
