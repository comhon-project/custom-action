<?php

namespace Comhon\CustomAction\Exceptions;

class SimulateMethodDoestExistException extends \Exception
{
    public function __construct(public string $class)
    {
        $this->message = "simulate method doesn't exist on class $class";
    }
}
