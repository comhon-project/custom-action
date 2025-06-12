<?php

namespace Comhon\CustomAction\Exceptions;

use Comhon\CustomAction\Models\Setting;

class SendEmailActionException extends RenderableException
{
    public function __construct(public Setting $setting, string $message)
    {
        $this->message = $message;
    }
}
