<?php

namespace Comhon\CustomAction\Exceptions;

class InvalidEventTypeException extends \Exception
{
    public function __construct(public $eventListener)
    {
        $class = get_class($eventListener);
        $this->message = "Invalid event {$this->eventListener->event} on model $class with id {$this->eventListener->id}";
    }
}
