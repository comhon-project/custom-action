<?php

namespace Comhon\CustomAction\Actions\Email\Exceptions;

use Comhon\CustomAction\Exceptions\RenderableException;
use Comhon\CustomAction\Models\Setting;

class SendEmailActionException extends RenderableException
{
    public function __construct(public Setting $setting, string $message)
    {
        $this->message = $message;
    }
}
