<?php

namespace Comhon\CustomAction\DTOs;

use Illuminate\Mail\Mailables\Address;

class EmailData
{
    public array $localizedMailInfosCache = [];

    public function __construct(
        public array $context,
        public array $recipients,
        public ?Address $from = null,
    ) {}
}
