<?php

namespace Comhon\CustomAction\Http\Controllers;

use Comhon\CustomAction\Contracts\CustomActionInterface;
use Comhon\CustomAction\Contracts\CustomUniqueActionInterface;
use Comhon\CustomAction\Contracts\TriggerableFromEventInterface;
use Comhon\CustomAction\Models\CustomActionSettings;
use Comhon\CustomAction\Models\CustomEventListener;
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
        if (! $resolver->isAllowedEvent($eventUniqueName)) {
            throw new NotFoundHttpException('not found');
        }
        $request->validate([
            'scope' => 'array|nullable',
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
            'scope' => 'array|nullable',
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
        $validated = $this->validateType(
            $request,
            $resolver,
            $eventListener,
            false
        );

        return new JsonResource($this->createAndAttachAction(
            $request,
            $resolver,
            $eventListener,
            $validated['type'],
        ));
    }

    /**
     * Store event listener action.
     *
     * @return \Illuminate\Http\Resources\Json\JsonResource
     */
    public function syncEventListenerAction(
        Request $request,
        ModelResolverContainer $resolver,
        CustomEventListener $eventListener
    ) {
        $validated = $this->validateType(
            $request,
            $resolver,
            $eventListener,
            true
        );

        $customActionSettings = CustomActionSettings::where('type', $validated['type'])->first();
        $eventListener->actions()->syncWithoutDetaching($customActionSettings);

        return new JsonResource($customActionSettings);
    }

    /**
     * Store event listener action.
     */
    private function createAndAttachAction(
        Request $request,
        ModelResolverContainer $resolver,
        CustomEventListener $eventListener,
        string $type,
    ) {
        $validated = $this->validateSettings($request, $resolver, $type);

        $customActionSettings = new CustomActionSettings();
        $customActionSettings->type = $type;
        $customActionSettings->settings = $validated['settings'];
        $customActionSettings->save();
        $eventListener->actions()->attach($customActionSettings);

        $customActionSettings = $customActionSettings->toArray();

        return $customActionSettings;
    }
    
    private function validateType(
        Request $request,
        ModelResolverContainer $resolver,
        CustomEventListener $eventListener,
        bool $mustBeUniqueAction
    ) {
        $eventClass = $resolver->getClass($eventListener->event);
        $allowedTypes = collect($eventClass::getAllowedActions())
            ->map(fn ($class) => $resolver->getUniqueName($class))
            ->filter(fn ($key) => $key !== null);

        return $request->validate([
            'type' => [
                'required',
                'string',
                'in:'.$allowedTypes->implode(','),
                function (string $attribute, $type, $fail) use ($resolver, $mustBeUniqueAction) {
                    $actionClass = $resolver->getClass($type);
                    $customAction = $actionClass ? app($actionClass) : null;
                    if (!$customAction instanceof CustomActionInterface) {
                        $fail("Action {$type} not found.");
                    }
                    if (!$customAction instanceof TriggerableFromEventInterface) {
                        $fail("The action {$type} is not an action triggerable from event.");
                    }
                    if (!$mustBeUniqueAction && $customAction instanceof CustomUniqueActionInterface) {
                        $fail("The action {$type} must not be a unique action.");
                    }
                    if ($mustBeUniqueAction && !$customAction instanceof CustomUniqueActionInterface) {
                        $fail("The action {$type} must be a unique action.");
                    }
                    if ($mustBeUniqueAction) {
                        $count = CustomActionSettings::where('type', $type)->count();
                        if ($count == 0) {
                            $fail("The action {$type} is not initialized.");
                        } elseif ($count > 1) {
                            throw new \Exception("several '$type' actions found");
                        }
                    }
                },
            ],
        ]);
    }
    
    private function validateSettings(
        Request $request,
        ModelResolverContainer $resolver,
        string $type,
    ) {
        $actionClass = $resolver->getClass($type);
        $customAction = app($actionClass);
        $rules = RulesManager::getSettingsRules($customAction->getSettingsSchema(), $customAction->hasTargetUser());
        return $request->validate($rules);
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
            if (! is_subclass_of($class, CustomUniqueActionInterface::class)) {
                $customActionSettings->delete();
            }
        });

        return response('', 204);
    }
}
