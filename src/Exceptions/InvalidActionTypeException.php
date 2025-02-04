<?php

namespace Comhon\CustomAction\Exceptions;

use Comhon\CustomAction\Models\Action;

class InvalidActionTypeException extends \Exception
{
    public function __construct(public Action $action)
    {
        $class = get_class($action);
        $this->message = "Invalid action type {$this->action->type} on model $class with id {$this->action->id}";
    }
}
