<?php

namespace Comhon\CustomAction\Http\Controllers;

use Comhon\CustomAction\Catalogs\EventCatalog;
use Comhon\CustomAction\Contracts\CustomEventInterface;
use Comhon\CustomAction\Contracts\HasBindingsInterface;
use Comhon\CustomAction\Facades\CustomActionModelResolver;
use Comhon\CustomAction\Models\EventListener;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class EventController extends Controller
{
    /**
     * Display a listing of events.
     */
    public function listEvents(EventCatalog $catalog)
    {
        $this->authorize('view-any', CustomEventInterface::class);

        return new JsonResource($catalog->get());
    }

    /**
     * Display event schema.
     */
    public function showEventSchema($eventUniqueName)
    {
        if (! CustomActionModelResolver::isAllowedEvent($eventUniqueName)) {
            throw new NotFoundHttpException('not found');
        }

        $this->authorize('view-schema', [CustomEventInterface::class, $eventUniqueName]);

        $eventClass = CustomActionModelResolver::getClass($eventUniqueName);
        $schema = [
            'bindings_schema' => is_subclass_of($eventClass, HasBindingsInterface::class)
                ? $eventClass::getBindingSchema()
                : [],
            'allowed_actions' => collect($eventClass::getAllowedActions())->map(function ($class) {
                return CustomActionModelResolver::getUniqueName($class);
            })->values(),
        ];

        return new JsonResource($schema);
    }

    /**
     * Display event listeners.
     */
    public function listEventListeners(Request $request, $eventUniqueName)
    {
        if (! CustomActionModelResolver::isAllowedEvent($eventUniqueName)) {
            throw new NotFoundHttpException('not found');
        }

        $this->authorize('view-listeners', [CustomEventInterface::class, $eventUniqueName]);

        $query = EventListener::where('event', $eventUniqueName);

        $name = $request->input('name');
        if ($name !== null) {
            $query->where('name', 'LIKE', "%$name%");
        }

        return JsonResource::collection($query->paginate());
    }
}
