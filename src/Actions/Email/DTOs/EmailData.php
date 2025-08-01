<?php

namespace Comhon\CustomAction\Actions\Email\DTOs;

use Comhon\CustomAction\Actions\Email\Support\EmailHelper;

class EmailData
{
    public array $localizedMailInfosCache = [];

    public array $to = [];

    public array $cc = [];

    public array $bcc = [];

    public function __construct(
        public array $context,
        array $recipients,
        public $from = null,
    ) {
        $this->to = EmailHelper::makeRecipientArrayList($recipients['to'] ?? null);
        $this->cc = EmailHelper::makeRecipientArrayList($recipients['cc'] ?? null);
        $this->bcc = EmailHelper::makeRecipientArrayList($recipients['bcc'] ?? null);
    }
}
