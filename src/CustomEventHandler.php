<?php

namespace Comhon\CustomAction;

use Comhon\CustomAction\Contracts\CustomEventInterface;
use Comhon\CustomAction\Contracts\TriggerableFromEventInterface;
use Comhon\CustomAction\Facades\CustomActionModelResolver;
use Comhon\CustomAction\Models\CustomEventListener;

class CustomEventHandler
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
        $listeners = CustomEventListener::with('eventActions.actionSettings')
            ->where('event', $eventUniqueName)->whereHas('eventActions')->get();
        $bindings = $event->getBindingValues();

        foreach ($listeners as $listener) {
            if (! $listener->scope || $this->matchScope($listener->scope, $bindings)) {
                foreach ($listener->eventActions as $eventAction) {
                    $customActionSettings = $eventAction->actionSettings;
                    $action = app(CustomActionModelResolver::getClass($eventAction->type));
                    if (! ($action instanceof TriggerableFromEventInterface)) {
                        throw new \Exception('invalid type '.$eventAction->type);
                    }
                    $action->handleFromEvent($event, $customActionSettings);
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
