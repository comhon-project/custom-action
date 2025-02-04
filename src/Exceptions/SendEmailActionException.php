<?php

namespace Comhon\CustomAction\Exceptions;

use Comhon\CustomAction\Models\Setting;

class SendEmailActionException extends \Exception
{
    public function __construct(public Setting $setting, string $message)
    {
        $this->message = $message;
    }
}
