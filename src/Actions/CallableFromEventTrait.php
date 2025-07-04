<?php

namespace Comhon\CustomAction\Actions;

use Comhon\CustomAction\Contracts\CustomEventInterface;
use Comhon\CustomAction\Contracts\FakableInterface;
use Comhon\CustomAction\Exceptions\SimulateActionException;
use Comhon\CustomAction\Models\Action;
use Comhon\CustomAction\Models\DefaultSetting;
use Comhon\CustomAction\Models\EventAction;
use Comhon\CustomAction\Models\LocalizedSetting;

trait CallableFromEventTrait
{
    public function __construct(
        protected Action $action,
        protected CustomEventInterface $event,
    ) {}

    public function getActionModel(): Action
    {
        return $this->action;
    }

    public function getEvent(): CustomEventInterface
    {
        return $this->event;
    }

    /**
     * Ensure the faked class does not modify the database.
     * Otherwise, wrap this call in a non-committed database transaction.
     */
    public static function buildFakeInstance(
        EventAction $eventAction,
        ?DefaultSetting $setting = null,
        ?LocalizedSetting $localizedSetting = null,
        ?array $state = null,
    ) {
        $eventClass = $eventAction->eventListener->getEventClass();
        if (! is_subclass_of($eventClass, FakableInterface::class)) {
            throw new SimulateActionException("cannot simulate action, event {$eventAction->eventListener->event} is not fakable");
        }

        $event = $eventClass::fake($state);
        $customAction = new static($eventAction, $event);

        $customAction->forcedSetting = $setting;
        $customAction->forcedLocalizedSetting = $localizedSetting;

        return $customAction;
    }
}
