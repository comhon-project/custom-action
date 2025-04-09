<?php

namespace Comhon\CustomAction\Listeners;

use Comhon\CustomAction\Context\ContextHelper;
use Comhon\CustomAction\Contracts\CallableFromEventInterface;
use Comhon\CustomAction\Contracts\CustomActionInterface;
use Comhon\CustomAction\Contracts\CustomEventInterface;
use Comhon\CustomAction\Contracts\ExposeContextInterface;
use Comhon\CustomAction\Events\EventActionError;
use Comhon\CustomAction\Exceptions\InvalidActionTypeException;
use Comhon\CustomAction\Facades\ContextScoper;
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

        $listeners = $event instanceof ExposeContextInterface
            ? ContextScoper::getEventListeners($query, ContextHelper::extractContext($event))
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

                    $actionClass::dispatch($eventAction, $event);
                } catch (\Throwable $th) {
                    EventActionError::dispatch($eventAction, $th);
                }
            }
        }
    }
}
