<?php

namespace Comhon\CustomAction\Listeners;

use Comhon\CustomAction\ActionSettings\SettingSelector;
use Comhon\CustomAction\Contracts\BindingsContainerInterface;
use Comhon\CustomAction\Contracts\CustomActionInterface;
use Comhon\CustomAction\Contracts\CustomEventInterface;
use Comhon\CustomAction\Contracts\HasBindingsInterface;
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
            ? $this->instanciateBindingsContainer($event)
            : null;
        $bindings = $bindingsContainer?->getBindingValues();

        $listeners = $bindings !== null
            ? BindingsScoper::getEventListeners($query, $bindings)
            : $query->lazy();

        foreach ($listeners as $listener) {
            /** @var \Comhon\CustomAction\Models\EventAction $eventAction */
            foreach ($listener->eventActions as $eventAction) {
                $actionClass = $eventAction->getActionClass();
                if (! is_subclass_of($actionClass, CustomActionInterface::class)) {
                    throw new \Exception("invalid action {$eventAction->type}, must be an action instance of CustomActionInterface");
                }
                $setting = SettingSelector::select($eventAction, $bindings);

                $actionClass::dispatch($setting, $bindingsContainer);
            }
        }
    }

    private function instanciateBindingsContainer(HasBindingsInterface $event): BindingsContainerInterface
    {
        return app(
            BindingsContainerInterface::class,
            [
                'bindingValues' => \Closure::fromCallable([$event, 'getBindingValues']),
                'bindingSchema' => $event->getBindingSchema(),
                'event' => $event,
            ]
        );
    }
}
