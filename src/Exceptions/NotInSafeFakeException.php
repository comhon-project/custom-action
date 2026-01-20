<?php

namespace Comhon\CustomAction\Exceptions;

use RuntimeException;

class NotInSafeFakeException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Not in safe fake context');
    }
}
