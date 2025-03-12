<?php

namespace Comhon\CustomAction\Actions;

use Comhon\CustomAction\Contracts\CustomEventInterface;
use Comhon\CustomAction\Models\Action;

trait CallableFromEventTrait
{
    public function __construct(
        protected Action $action,
        protected CustomEventInterface $event,
    ) {}

    public function getAction(): Action
    {
        return $this->action;
    }

    public function getEvent(): CustomEventInterface
    {
        return $this->event;
    }
}
