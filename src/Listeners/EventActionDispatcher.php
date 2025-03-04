<?php

namespace Comhon\CustomAction\Listeners;

use Comhon\CustomAction\Bindings\EventBindingsContainer;
use Comhon\CustomAction\Contracts\CallableFromEventInterface;
use Comhon\CustomAction\Contracts\CustomActionInterface;
use Comhon\CustomAction\Contracts\CustomEventInterface;
use Comhon\CustomAction\Contracts\HasBindingsInterface;
use Comhon\CustomAction\Events\EventActionError;
use Comhon\CustomAction\Exceptions\InvalidActionTypeException;
use Comhon\CustomAction\Facades\BindingsScoper;
use Comhon\CustomAction\Facades\CustomActionModelResolver;
use Comhon\CustomAction\Models\EventListener;

class EventActionDispatcher
{
    /**
     * Handle the event.
     *
     * @return void
     */
    public function handle(CustomEventInterface $event)
    {
        $eventUniqueName = CustomActionModelResolver::getUniqueName(get_class($event));
        $query = EventListener::with('eventActions.defaultSetting')
            ->where('event', $eventUniqueName)->whereHas('eventActions');

        $bindingsContainer = $event instanceof HasBindingsInterface
            ? new EventBindingsContainer($event)
            : null;
        $bindings = $bindingsContainer?->getBindingValues();

        $listeners = $bindings !== null
            ? BindingsScoper::getEventListeners($query, $bindings)
            : $query->lazy();

        foreach ($listeners as $listener) {
            /** @var \Comhon\CustomAction\Models\EventAction $eventAction */
            foreach ($listener->eventActions as $eventAction) {
                try {
                    $actionClass = $eventAction->getActionClass();
                    if (! is_subclass_of($actionClass, CustomActionInterface::class)) {
                        throw new InvalidActionTypeException($eventAction);
                    }
                    if (! is_subclass_of($actionClass, CallableFromEventInterface::class)) {
                        throw new InvalidActionTypeException($eventAction);
                    }

                    $actionClass::dispatch($eventAction, $bindingsContainer);
                } catch (\Throwable $th) {
                    EventActionError::dispatch($eventAction, $th);
                }
            }
        }
    }
}
