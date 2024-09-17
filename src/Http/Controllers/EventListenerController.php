<?php

namespace Comhon\CustomAction\Http\Controllers;

use Comhon\CustomAction\Facades\CustomActionModelResolver;
use Comhon\CustomAction\Models\EventListener;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class EventListenerController extends Controller
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
        $this->authorize('create', [EventListener::class, $eventClass]);

        $validated = $this->validateEventListenerRequest($request);

        $eventListener = new EventListener($validated);
        $eventListener->event = $eventUniqueName;
        $eventListener->save();

        return new JsonResource($eventListener);
    }

    /**
     * Update event listener.
     *
     * @return \Illuminate\Http\Resources\Json\JsonResource
     */
    public function update(Request $request, EventListener $eventListener)
    {
        $this->authorize('update', $eventListener);

        $validated = $this->validateEventListenerRequest($request);
        $eventListener->fill($validated);
        $eventListener->save();

        return new JsonResource($eventListener);
    }

    /**
     * Delete event listener.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(EventListener $eventListener)
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
    public function listEventListenerActions(Request $request, EventListener $eventListener)
    {
        $this->authorize('view', $eventListener);

        $query = $eventListener->eventActions()->with('actionSettings');

        $name = $request->input('name');
        if ($name !== null) {
            $query->where('name', 'LIKE', "%$name%");
        }

        return JsonResource::collection($query->paginate());
    }

    private function validateEventListenerRequest(Request $request)
    {
        return $request->validate([
            'scope' => 'array|nullable',
            'name' => 'required|string|max:63',
        ]);
    }
}
