<?php

namespace Comhon\CustomAction\Contracts;

use Comhon\CustomAction\Bindings\EventBindingsContainer;

interface CallableFromEventInterface
{
    public static function dispatch(...$arguments);

    public function getEventBindingsContainer(): ?EventBindingsContainer;
}
