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
use Comhon\CustomAction\Models\EventAction;
use Comhon\CustomAction\Models\EventListener;
use Illuminate\Database\DeadlockException;
use Illuminate\Support\Facades\DB;

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
            /** @var EventAction $eventAction */
            foreach ($listener->eventActions as $eventAction) {
                $transactionLevel = DB::transactionLevel();
                try {
                    $actionClass = $eventAction->getActionClass();
                    if (
                        ! is_subclass_of($actionClass, CustomActionInterface::class)
                        || ! is_subclass_of($actionClass, CallableFromEventInterface::class)
                    ) {
                        throw new InvalidActionTypeException($eventAction);
                    }

                    $this->dispatchAction($actionClass, $eventAction, $event);
                } catch (\Throwable $th) {
                    if ($this->isTransactionLost($th, $transactionLevel)) {
                        throw $th;
                    }
                    EventActionError::dispatch($eventAction, $th);
                }
            }
        }
    }

    /**
     * Whether the transaction started before the action is lost (deadlock, lost connection, savepoint not restored...),
     * in that case it must fail (or be retried).
     */
    private function isTransactionLost(\Throwable $th, int $transactionLevel): bool
    {
        return $transactionLevel > 0
            && ($th instanceof DeadlockException || DB::transactionLevel() !== $transactionLevel);
    }

    /**
     * Inside a transaction, the action is executed in a savepoint,
     * so if it fails, only its changes are rolled back.
     */
    private function dispatchAction(string $actionClass, EventAction $eventAction, CustomEventInterface $event): void
    {
        $dispatch = function () use ($actionClass, $eventAction, $event) {
            // not returned, the action is dispatched when the pending dispatch is destructed,
            // so it must be destructed here (inside the transaction).
            $actionClass::dispatch($eventAction, $event);
        };

        if (DB::transactionLevel() > 0) {
            DB::transaction($dispatch);
        } else {
            $dispatch();
        }
    }
}
