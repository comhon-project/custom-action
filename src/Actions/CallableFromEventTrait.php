<?php

namespace Comhon\CustomAction\Actions;

use Comhon\CustomAction\Bindings\EventBindingsContainer;
use Comhon\CustomAction\Models\Action;

trait CallableFromEventTrait
{
    public function __construct(
        protected Action $action,
        protected ?EventBindingsContainer $eventBindingsContainer = null,
    ) {}

    public function getEventBindingsContainer(): ?EventBindingsContainer
    {
        return $this->eventBindingsContainer;
    }
}
