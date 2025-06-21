<?php

namespace App\Actions;

use Comhon\CustomAction\Actions\Email\AbstractSendManualEmail;

class SendManualUserRegisteredEmail extends AbstractSendManualEmail
{
    public function __construct(
        public array $users,
        private bool $shouldGroup = false,
        protected $from = null,
        protected ?string $subject = null,
        protected ?string $body = null,
    ) {}

    protected function shouldGroupRecipients(): bool
    {
        return $this->shouldGroup;
    }

    protected static function getCommonContextSchema(): ?array
    {
        return [
            'users' => 'array',
            'users.*' => 'is:mailable-entity',
            'users.*.first_name' => 'string',
        ];
    }
}
