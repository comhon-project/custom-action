<?php

namespace Comhon\CustomAction;

use Comhon\CustomAction\Contracts\CustomActionInterface;
use Comhon\CustomAction\Contracts\CustomEventInterface;
use Comhon\CustomAction\Contracts\HasBindingsInterface;
use Comhon\CustomAction\Facades\CustomActionModelResolver;
use Comhon\CustomAction\Models\EventListener;

class EventActionDispatcher
{
    public $afterCommit = true;

    /**
     * Handle the event.
     *
     * @return void
     */
    public function handle(CustomEventInterface $event)
    {
        $eventUniqueName = CustomActionModelResolver::getUniqueName(get_class($event));
        $listeners = EventListener::with('eventActions.actionSettings')
            ->where('event', $eventUniqueName)->whereHas('eventActions')->get();

        $bindingsContainer = $event instanceof HasBindingsInterface ?
            new BindingsContainer(\Closure::fromCallable([$event, 'getBindingValues']), $event->getBindingSchema())
            : null;
        $bindings = $bindingsContainer?->getBindingValues() ?? [];

        foreach ($listeners as $listener) {
            if (! $listener->scope || $this->matchScope($listener->scope, $bindings)) {
                foreach ($listener->eventActions as $eventAction) {
                    $actionSettings = $eventAction->actionSettings;
                    $action = app(CustomActionModelResolver::getClass($eventAction->type));
                    if (! ($action instanceof CustomActionInterface)) {
                        throw new \Exception('invalid type '.$eventAction->type);
                    }
                    $action->handle($actionSettings, $bindingsContainer);
                }
            }
        }
    }

    public function matchScope(array $scope, array $bindings)
    {
        $match = true;
        foreach ($scope as $model => $values) {
            foreach ($values as $property => $value) {
                if (($bindings[$model][$property] ?? null) != $value) {
                    $match = false;
                    break 2;
                }
            }
        }

        return $match;
    }
}
