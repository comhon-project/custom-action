<?php

namespace Comhon\CustomAction\Http\Controllers;

use Comhon\CustomAction\Models\CustomActionSettings;
use Comhon\CustomAction\Models\CustomEventListener;
use Comhon\CustomAction\Contracts\CustomUniqueActionInterface;
use Comhon\CustomAction\Resolver\ModelResolverContainer;
use Comhon\CustomAction\Rules\RulesManager;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CustomEventListenerController extends Controller
{
    /**
     * Store event listener.
     *
     * @return \Illuminate\Http\Resources\Json\JsonResource
     */
    public function store(Request $request, ModelResolverContainer $resolver, $eventUniqueName)
    {
        if (!$resolver->isAllowedEvent($eventUniqueName)) {
            throw new NotFoundHttpException('not found');
        }
        $request->validate([
            'scope' => 'array|nullable'
        ]);

        $eventListener = new CustomEventListener();
        $eventListener->event = $eventUniqueName;
        $eventListener->scope = $request->input('scope');
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
        $validated = $request->validate([
            'scope' => 'array|nullable'
        ]);
        $eventListener->scope = $validated['scope'] ?? null;
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
        return JsonResource::collection($eventListener->load('actions:id,type')->actions);
    }

    /**
     * Store event listener action.
     *
     * @return \Illuminate\Http\Resources\Json\JsonResource
     */
    public function storeEventListenerAction(
        Request $request,
        ModelResolverContainer $resolver,
        CustomEventListener $eventListener
    ) {
        $eventClass = $resolver->getClass($eventListener->event);
        $allowedTypes = collect($eventClass::getAllowedActions())
            ->map(fn ($class) => $resolver->getUniqueName($class))
            ->filter(fn ($key) => $key !== null);

        $validated = $request->validate([
            'type' => 'required|string|in:' . $allowedTypes->implode(','),
        ]);
        $type = $validated['type'];
        $actionClass = $resolver->getClass($type);
        $customAction = app($actionClass);
        $rules = RulesManager::getSettingsRules($customAction->getSettingsSchema(), $customAction->hasTargetUser());
        $validated = $request->validate($rules);

        $customActionSettings = new CustomActionSettings();
        $customActionSettings->type = $type;
        $customActionSettings->settings = $validated['settings'] ?? [];
        $customActionSettings->save();
        $eventListener->actions()->attach($customActionSettings);

        $customActionSettings = $customActionSettings->toArray();
        $customActionSettings['type'] = $type;
        return new JsonResource($customActionSettings);
    }

    /**
     * remove event listener action.
     *
     * @return \Illuminate\Http\Response
     */
    public function removeEventListenerAction(
        ModelResolverContainer $resolver,
        CustomEventListener $eventListener,
        CustomActionSettings $customActionSettings
    ) {
        DB::transaction(function () use ($eventListener, $customActionSettings, $resolver) {
            $eventListener->actions()->detach($customActionSettings);
            $class = $resolver->getClass($customActionSettings->type);
            if (!is_subclass_of($class, CustomUniqueActionInterface::class)) {
                $customActionSettings->delete();
            }
        });
        return response('', 204);
    }
}