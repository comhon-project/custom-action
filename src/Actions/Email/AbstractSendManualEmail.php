<?php

namespace Comhon\CustomAction\Actions\Email;

use Comhon\CustomAction\Actions\CallableManuallyTrait;
use Comhon\CustomAction\Models\LocalizedSetting;
use Illuminate\Contracts\Mail\Attachable;
use Illuminate\Mail\Mailables\Address;

class AbstractSendManualEmail extends AbstractSendGenericEmail
{
    use CallableManuallyTrait;

    protected $to = null;

    protected $cc = null;

    protected $bcc = null;

    protected $from = null;

    protected ?iterable $attachments = null;

    protected ?string $subject = null;

    protected ?string $body = null;

    protected ?bool $grouped = null;

    public function __construct(
        $to = null,
        $cc = null,
        $bcc = null,
        $from = null,
        ?iterable $attachments = null,
        ?string $subject = null,
        ?string $body = null,
        ?bool $grouped = null,
    ) {
        $this->to = $to;
        $this->cc = $cc;
        $this->bcc = $bcc;
        $this->from = $from;
        $this->attachments = $attachments;
        $this->subject = $subject;
        $this->body = $body;
        $this->grouped = $grouped;
    }

    protected function getFrom(): ?Address
    {
        $from = $this->from ?? parent::getFrom();

        return $from ? $this->normalizeAddress($from) : null;
    }

    /**
     * Get email recipents
     *
     * @return array{to: mixed, cc: mixed, bcc: mixed}
     */
    protected function getRecipients(): array
    {
        $recipients = parent::getRecipients();

        foreach (static::RECIPIENT_TYPES as $recipientType) {
            if (! empty($this->$recipientType)) {
                $recipients[$recipientType] = $this->$recipientType;
            }
        }

        return $recipients;
    }

    protected function getSubject(LocalizedSetting $localizedSetting): string
    {
        return $this->subject ?? parent::getSubject($localizedSetting);
    }

    protected function getBody(LocalizedSetting $localizedSetting): string
    {
        return $this->body ?? parent::getBody($localizedSetting);
    }

    protected function getAttachments(LocalizedSetting $localizedSetting): ?iterable
    {
        return $this->attachments ?? parent::getAttachments($localizedSetting);
    }

    protected function shouldGroupRecipients(): bool
    {
        return $this->grouped ?? parent::shouldGroupRecipients();
    }

    public function from($from): static
    {
        $this->from = $from;

        return $this;
    }

    public function to($users): static
    {
        $this->to = $users;

        return $this;
    }

    public function cc($users): static
    {
        $this->cc = $users;

        return $this;
    }

    public function bcc($users): static
    {
        $this->bcc = $users;

        return $this;
    }

    /**
     * @param  Attachable|Attachable[]  $attachments
     */
    public function attachments(Attachable|array $attachments): static
    {
        $this->attachments = $attachments instanceof Attachable
            ? [$attachments]
            : $attachments;

        return $this;
    }

    public function subject(string $subject): static
    {
        $this->subject = $subject;

        return $this;
    }

    public function body(string $body): static
    {
        $this->body = $body;

        return $this;
    }

    /**
     * Send only one email with all defined "to" at once
     */
    public function groupRecipients(bool $group): static
    {
        $this->grouped = $group;

        return $this;
    }
}
