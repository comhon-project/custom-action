<?php

namespace Comhon\CustomAction\Http\Controllers;

use Comhon\CustomAction\Contracts\CustomActionInterface;
use Comhon\CustomAction\Facades\CustomActionModelResolver;
use Comhon\CustomAction\Models\DefaultSetting;
use Comhon\CustomAction\Models\EventAction;
use Comhon\CustomAction\Models\EventListener;
use Comhon\CustomAction\Models\ScopedSetting;
use Comhon\CustomAction\Services\ActionService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

class EventActionController extends Controller
{
    use ActionTrait;

    /**
     * Store event listener action.
     */
    public function store(Request $request, ActionService $actionService, EventListener $eventListener)
    {
        $this->authorize('create', [EventAction::class, $eventListener]);

        $validated = $this->validateStoreRequest($request, $eventListener);

        $eventAction = new EventAction;
        $eventAction->eventListener()->associate($eventListener->id);
        $eventAction->type = $validated['type'];
        $eventAction->name = $validated['name'];

        $defaultSetting = null;
        if ($request->filled('settings')) {
            $validated = $request->validate($actionService->getSettingsRules($eventAction, false));
            $defaultSetting = new DefaultSetting;
            $defaultSetting->settings = $validated['settings'];
        }

        DB::transaction(function () use ($eventAction, $defaultSetting) {
            $eventAction->save();

            if ($defaultSetting) {
                $defaultSetting->action()->associate($eventAction);
                $defaultSetting->save();
                $defaultSetting->unsetRelation('action'); // to avoid infinite loop
            }

            $eventAction->setRelation('defaultSetting', $defaultSetting);
        });

        return new JsonResource($eventAction);
    }

    /**
     * Show event listener action.
     */
    public function show(EventAction $eventAction)
    {
        $this->authorize('view', $eventAction);

        return new JsonResource($eventAction->load('defaultSetting'));
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

    public function listScopedSettings(Request $request, EventAction $eventAction)
    {
        return $this->listActionScopedSettings($request, $eventAction);
    }

    public function storeDefaultSetting(Request $request, ActionService $actionService, EventAction $eventAction): JsonResource
    {
        $this->authorize('create', [DefaultSetting::class, $eventAction]);

        $defaultSetting = $actionService->storeDefaultSetting($eventAction, $request->input());

        return new JsonResource($defaultSetting);
    }

    public function storeScopedSetting(Request $request, ActionService $actionService, EventAction $eventAction): JsonResource
    {
        $this->authorize('create', [ScopedSetting::class, $eventAction]);

        $scopedSetting = $actionService->storeScopedSetting($eventAction, $request->input());

        return new JsonResource($scopedSetting);
    }

    private function validateStoreRequest(Request $request, EventListener $eventListener)
    {
        $eventClass = $eventListener->getEventClass();
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
                    if (! is_subclass_of($actionClass, CustomActionInterface::class)) {
                        $fail("Action {$type} not found.");
                    }
                },
            ],
            'name' => 'required|string|max:63',
        ]);
    }
}
