<?php

namespace Comhon\CustomAction\DTOs;

use Comhon\CustomAction\Mail\Custom;

class CustomMailable
{
    public function __construct(
        public Custom $mailable,
        public $to,
    ) {}
}
