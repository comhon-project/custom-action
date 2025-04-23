<?php

namespace Comhon\CustomAction\Actions\Email;

use Comhon\CustomAction\Actions\CallableFromEventTrait;
use Comhon\CustomAction\Contracts\CallableFromEventInterface;

class SendAutomaticEmail extends AbstractSendGenericEmail implements CallableFromEventInterface
{
    use CallableFromEventTrait;
}
