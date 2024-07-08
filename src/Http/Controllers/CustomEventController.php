<?php

namespace Comhon\CustomAction\Http\Controllers;

use Comhon\CustomAction\Contracts\CustomEventInterface;
use Comhon\CustomAction\Facades\CustomActionModelResolver;
use Comhon\CustomAction\Models\CustomEventListener;
use Illuminate\Http\Resources\Json\JsonResource;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CustomEventController extends Controller
{
    /**
     * Display a listing of events.
     *
     * @return \Illuminate\Http\Resources\Json\JsonResource
     */
    public function listEvents()
    {
        $this->authorize('view-any', CustomEventInterface::class);

        $events = config('custom-action.events') ?? [];
        $events = collect($events)->map(function ($class) {
            $uniqueName = CustomActionModelResolver::getUniqueName($class);

            return [
                'type' => $uniqueName,
                'name' => trans('custom-action::messages.events.'.$uniqueName),
            ];
        })->values();

        return new JsonResource($events);
    }

    /**
     * Display event schema.
     *
     * @return \Illuminate\Http\Resources\Json\JsonResource
     */
    public function showEventSchema($eventUniqueName)
    {
        if (! CustomActionModelResolver::isAllowedEvent($eventUniqueName)) {
            throw new NotFoundHttpException('not found');
        }
        $eventClass = CustomActionModelResolver::getClass($eventUniqueName);
        if (! is_subclass_of($eventClass, CustomEventInterface::class)) {
            throw new \Exception("invalid event '$eventClass', it should implement CustomEventInterface");
        }

        $this->authorize('view', [CustomEventInterface::class, $eventClass]);

        $schema = ['binding_schema' => $eventClass::getBindingSchema()];
        $schema['allowed_actions'] = collect($eventClass::getAllowedActions())->map(function ($class) {
            $uniqueName = CustomActionModelResolver::getUniqueName($class);

            return [
                'type' => $uniqueName,
                'name' => trans('custom-action::messages.actions.'.$uniqueName),
            ];
        })->values();

        return new JsonResource($schema);
    }

    /**
     * Display event listeners.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function listEventListeners($eventUniqueName)
    {
        if (! CustomActionModelResolver::isAllowedEvent($eventUniqueName)) {
            throw new NotFoundHttpException('not found');
        }

        $eventClass = CustomActionModelResolver::getClass($eventUniqueName);
        $this->authorize('view', [CustomEventInterface::class, $eventClass]);

        return JsonResource::collection(CustomEventListener::where('event', $eventUniqueName)->get());
    }
}
