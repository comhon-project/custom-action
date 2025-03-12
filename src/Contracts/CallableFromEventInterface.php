<?php

namespace Comhon\CustomAction\Contracts;

interface CallableFromEventInterface
{
    public static function dispatch(...$arguments);

    public function getEvent(): CustomEventInterface;
}
