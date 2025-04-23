<?php

namespace Comhon\CustomAction\Actions\Email;

use Comhon\CustomAction\Actions\CallableManuallyTrait;
use Comhon\CustomAction\Contracts\HasTimezonePreferenceInterface;
use Comhon\CustomAction\Contracts\MailableEntityInterface;
use Comhon\CustomAction\Exceptions\SendEmailActionException;
use Comhon\CustomAction\Models\LocalizedSetting;
use Comhon\TemplateRenderer\Facades\Template;
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

    final public function preview(bool $renderTemplate = true)
    {
        $context = $this->getExposedValidatedContext(true);
        $recipients = $this->getRecipients();
        $tos = $recipients['to'] ?? null;

        $groupRecipients = $this->shouldGroupRecipients() && count($tos) > 1;
        if ($groupRecipients) {
            $tos = [$tos];
        }
        if (count($tos) > 1) {
            throw new SendEmailActionException($this->getSetting(), 'must have one and only one email to send to generate preview');
        }

        if ($groupRecipients) {
            $localizedSetting = $this->getLocalizedSettingOrFail($this->getGroupedLocale());
            $preferredTimezone = $this->getGroupedTimezone();
        } else {
            $to = collect($tos)->pop();
            $localizedSetting = $this->getLocalizedSettingOrFail(is_string($to) ? null : $to);

            // we expose 'to' value in email body or subject only if recipient is a MailableEntityInterface
            $context['to'] = $to instanceof MailableEntityInterface ? $to->getExposableValues() : null;

            $preferredTimezone = $to instanceof HasTimezonePreferenceInterface ? $to->preferredTimezone() : null;
        }

        return $renderTemplate
            ? Template::render(
                $this->getBody($localizedSetting),
                $context,
                $localizedSetting->locale,
                null,
                $preferredTimezone
            )
            : $this->getBody($localizedSetting);
    }
}
