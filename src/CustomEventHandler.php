<?php

namespace Comhon\CustomAction;

use Comhon\CustomAction\Contracts\CustomEventInterface;
use Comhon\CustomAction\Contracts\TriggerableFromEventInterface;
use Comhon\CustomAction\Models\CustomEventListener;
use Comhon\CustomAction\Resolver\ModelResolverContainer;

class CustomEventHandler
{
    public $afterCommit = true;

    public function __construct(private ModelResolverContainer $resolver)
    {
    }

    /**
     * Handle the event.
     *
     * @return void
     */
    public function handle(CustomEventInterface $event)
    {
        $eventUniqueName = $this->resolver->getUniqueName(get_class($event));
        $listeners = CustomEventListener::with('actions')
            ->where('event', $eventUniqueName)->whereHas('actions')->get();
        $bindings = $event->getBindingValues();

        foreach ($listeners as $listener) {
            if (! $listener->scope || $this->matchScope($listener->scope, $bindings)) {
                foreach ($listener->actions as $action) {
                    $handler = app($this->resolver->getClass($action->type));
                    if (! ($handler instanceof TriggerableFromEventInterface)) {
                        throw new \Exception('invalid type '.$action->type);
                    }
                    $handler->handleFromEvent($event, $action);
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
