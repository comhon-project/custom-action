<?php

namespace Comhon\CustomAction\Contracts;

use Comhon\CustomAction\Bindings\EventBindingsContainer;

interface CallableFromEventInterface
{
    public function getEventBindingsContainer(): ?EventBindingsContainer;
}
