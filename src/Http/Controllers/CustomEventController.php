<?php

namespace Comhon\CustomAction\Http\Controllers;

use Comhon\CustomAction\Contracts\CustomEventInterface;
use Comhon\CustomAction\Models\CustomEventListener;
use Comhon\CustomAction\Resolver\ModelResolverContainer;
use Illuminate\Http\Resources\Json\JsonResource;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CustomEventController extends Controller
{
    /**
     * Display a listing of events.
     *
     * @return \Illuminate\Http\Resources\Json\JsonResource
     */
    public function listEvents(ModelResolverContainer $resolver)
    {
        $this->authorize('view-any', CustomEventInterface::class);

        $events = $resolver->getUniqueNames(ModelResolverContainer::EVENT_SCOPE);
        $events = collect($events)->map(function ($eventUniqueName) {
            return [
                'key' => $eventUniqueName,
                'name' => trans('custom-action::messages.events.'.$eventUniqueName),
            ];
        })->values();

        return new JsonResource($events);
    }

    /**
     * Display event schema.
     *
     * @return \Illuminate\Http\Resources\Json\JsonResource
     */
    public function showEventSchema(ModelResolverContainer $resolver, $eventUniqueName)
    {
        if (! $resolver->isAllowedEvent($eventUniqueName)) {
            throw new NotFoundHttpException('not found');
        }
        $eventClass = $resolver->getClass($eventUniqueName);
        if (! is_subclass_of($eventClass, CustomEventInterface::class)) {
            throw new \Exception("invalid event '$eventClass', it should implement CustomEventInterface");
        }

        $this->authorize('view', [CustomEventInterface::class, $eventClass]);

        $schema = ['binding_schema' => [], 'allowed_actions' => []];
        $schema['binding_schema'] = $eventClass::getBindingSchema();
        foreach ($eventClass::getAllowedActions() as $actionClass) {
            $actionUniqueName = $resolver->getUniqueName($actionClass);
            if ($actionUniqueName) {
                $schema['allowed_actions'][] = $actionUniqueName;
            }
        }

        return new JsonResource($schema);
    }

    /**
     * Display event listeners.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function listEventListeners(ModelResolverContainer $resolver, $eventUniqueName)
    {
        if (! $resolver->isAllowedEvent($eventUniqueName)) {
            throw new NotFoundHttpException('not found');
        }

        $eventClass = $resolver->getClass($eventUniqueName);
        $this->authorize('view', [CustomEventInterface::class, $eventClass]);

        return JsonResource::collection(CustomEventListener::where('event', $eventUniqueName)->get());
    }
}
