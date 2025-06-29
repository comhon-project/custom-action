<?php

namespace Comhon\CustomAction\Actions\Email\DTOs;

use Comhon\CustomAction\Actions\Email\Mailable\Custom;

class CustomMailable
{
    public function __construct(
        public Custom $mailable,
        public $to,
    ) {}
}
