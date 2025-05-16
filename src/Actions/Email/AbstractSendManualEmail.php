<?php

namespace Comhon\CustomAction\Actions\Email;

use Comhon\CustomAction\Actions\CallableManuallyTrait;
use Comhon\CustomAction\Models\LocalizedSetting;
use Illuminate\Mail\Mailables\Address;

abstract class AbstractSendManualEmail extends AbstractSendGenericEmail
{
    use CallableManuallyTrait;

    protected function getFrom(): ?Address
    {
        $from = $this->from ?? parent::getFrom() ?? null;

        return $from ? $this->normalizeAddress($from) : null;
    }

    protected function getSubject(LocalizedSetting $localizedSetting): string
    {
        return $this->subject ?? parent::getSubject($localizedSetting);
    }

    protected function getBody(LocalizedSetting $localizedSetting): string
    {
        return $this->body ?? parent::getBody($localizedSetting);
    }
}
