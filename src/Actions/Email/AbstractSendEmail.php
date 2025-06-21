<?php

declare(strict_types=1);

namespace Comhon\CustomAction\Actions\Email;

use Comhon\CustomAction\Actions\InteractWithContextTrait;
use Comhon\CustomAction\Actions\InteractWithSettingsTrait;
use Comhon\CustomAction\Contracts\CustomActionInterface;
use Comhon\CustomAction\Contracts\ExposeContextInterface;
use Comhon\CustomAction\Contracts\HasContextKeysIgnoredForScopedSettingInterface;
use Comhon\CustomAction\Contracts\HasTimezonePreferenceInterface;
use Comhon\CustomAction\Contracts\MailableEntityInterface;
use Comhon\CustomAction\Contracts\SimulatableInterface;
use Comhon\CustomAction\DTOs\CustomMailable;
use Comhon\CustomAction\DTOs\EmailData;
use Comhon\CustomAction\Exceptions\SendEmailActionException;
use Comhon\CustomAction\Mail\Custom;
use Comhon\CustomAction\Models\LocalizedSetting;
use Comhon\CustomAction\Rules\RuleHelper;
use Comhon\CustomAction\Support\EmailHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;

abstract class AbstractSendEmail implements CustomActionInterface, ExposeContextInterface, HasContextKeysIgnoredForScopedSettingInterface, SimulatableInterface
{
    use Dispatchable,
        InteractsWithQueue,
        InteractWithContextTrait,
        InteractWithSettingsTrait,
        Queueable,
        SerializesModels;

    const RECIPIENT_TYPES = ['to', 'cc', 'bcc'];

    /**
     * Indicates if each mail should be sent asynchronously.
     *
     * @var bool
     */
    protected $sendAsynchronously = false;

    /**
     * Get email sender.
     *
     * should return an address only if you want another sender
     * than the the default one defined on the application config
     */
    abstract protected function getFrom(): ?Address;

    /**
     * Get email recipents
     *
     * @return array{to: mixed, cc: mixed, bcc: mixed}
     */
    abstract protected function getRecipients(): array;

    /**
     * Get email subject.
     *
     * returned subject can be a text template that will be processed to do some replacements
     */
    abstract protected function getSubject(LocalizedSetting $localizedSetting): string;

    /**
     * Get email body
     *
     * returned body can be a html template that will be processed to do some replacements
     */
    abstract protected function getBody(LocalizedSetting $localizedSetting): string;

    /**
     * Get email attachements
     */
    abstract protected function getAttachments(LocalizedSetting $localizedSetting): ?iterable;

    /**
     * Get action context schema.
     *
     * Common context + recipient specific context
     */
    final public static function getContextSchema(): array
    {
        return [
            ...static::getCommonContextSchema() ?? [],
            'to' => ['nullable', RuleHelper::getRuleName('is').':mailable-entity'],
            'default_timezone' => 'string',
            'preferred_timezone' => 'string',
        ];
    }

    public static function getContextKeysIgnoredForScopedSetting(): array
    {
        return ['to', 'default_timezone', 'preferred_timezone'];
    }

    /**
     * Get action common context schema.
     *
     * Common context is the context that is the same for all recipients
     */
    protected static function getCommonContextSchema(): ?array
    {
        return [];
    }

    /**
     * Determine whether an email should be sent to each recipient
     * individually or as a single email to all recipients.
     */
    protected function shouldGroupRecipients(): bool
    {
        return false;
    }

    /**
     * Determine which locale should be used when sending grouped emails.
     */
    protected function getGroupedLocale(): string
    {
        return App::getLocale();
    }

    /**
     * Determine the preferred time zone to use during template rendering when sending grouped emails.
     */
    protected function getGroupedTimezone(): string
    {
        return config('app.timezone');
    }

    /**
     * Determine the default timezone to use during template rendering.
     */
    protected function getDefaultTimezone(?string $preferredTimezone): string
    {
        return config('app.timezone');
    }

    private function buildEmailDataConainer(): EmailData
    {
        return new EmailData(
            $this->getExposedValidatedContext(true),
            $this->getRecipients(),
            $this->getFrom(),
        );
    }

    private function getTos(EmailData $emailData): array
    {
        $tos = $emailData->to;

        if (empty($tos)) {
            throw new SendEmailActionException($this->getSetting(), 'there is no mail recipients defined');
        }

        $groupRecipients = $this->shouldGroupRecipients() && count($tos) > 1;
        if ($groupRecipients) {
            $tos = [collect($tos)];
        }

        return $tos;
    }

    private function buildCustomMailable(EmailData $emailData, $to): CustomMailable
    {
        // if $to is a collection, that means there are several recipients for one mail.
        if ($to instanceof Collection) {
            $localizedSetting = $this->getLocalizedSettingOrFail($this->getGroupedLocale());
            $normalizedTo = $this->normalizeAddresses($to);
            $preferredTimezone = $this->getGroupedTimezone();
        } else {
            $localable = is_array($to) || $to instanceof HasLocalePreference;
            $localizedSetting = $this->getLocalizedSettingOrFail($localable ? $to : null);
            $normalizedTo = $this->normalizeAddress($to);

            $emailData->context['to'] = $to instanceof MailableEntityInterface
                ? $to->getExposableValues()
                : ['email' => $normalizedTo->address, 'name' => $normalizedTo->name];

            $preferredTimezone = $to instanceof HasTimezonePreferenceInterface
                ? $to->preferredTimezone()
                : null;
        }

        $locale = $localizedSetting->locale;
        $emailData->localizedMailInfosCache[$locale] ??= $this->getLocalizedMailInfos(
            $localizedSetting,
            $emailData->from
        );

        return new CustomMailable(
            new Custom(
                $emailData->localizedMailInfosCache[$locale],
                $emailData->context,
                $locale,
                $this->getDefaultTimezone($preferredTimezone),
                $preferredTimezone,
            ),
            $normalizedTo,
        );
    }

    public function handle()
    {
        $emailData = $this->buildEmailDataConainer();
        $cc = EmailHelper::normalizeAddresses($emailData->cc);
        $bcc = EmailHelper::normalizeAddresses($emailData->bcc);

        foreach ($this->getTos($emailData) as $to) {
            $customMailable = $this->buildCustomMailable($emailData, $to);
            $sendMethod = $this->sendAsynchronously ? 'queue' : 'send';

            Mail::to($customMailable->to)
                ->cc($cc)
                ->bcc($bcc)
                ->$sendMethod($customMailable->mailable);
        }
    }

    final protected function getLocalizedMailInfos(LocalizedSetting $localizedSetting, ?Address $from)
    {
        return [
            'from' => $from,
            'subject' => $this->getSubject($localizedSetting),
            'body' => $this->getBody($localizedSetting),
            'attachments' => $this->getAttachments($localizedSetting),
        ];
    }

    final protected function normalizeAddresses(iterable $values): array
    {
        return EmailHelper::normalizeAddresses($values);
    }

    final protected function normalizeAddress($value): Address
    {
        return EmailHelper::normalizeAddress($value);
    }

    public function simulate()
    {
        $simulations = [];
        $emailData = $this->buildEmailDataConainer();
        $cc = EmailHelper::normalizeAddresses($emailData->cc);
        $bcc = EmailHelper::normalizeAddresses($emailData->bcc);

        foreach ($this->getTos($emailData) as $to) {
            $customMailable = $this->buildCustomMailable($emailData, $to);

            $simulations[] = [
                'to' => $customMailable->to,
                'cc' => $cc,
                'bcc' => $bcc,
                'from' => $customMailable->mailable->envelope()->from,
                'subject' => $customMailable->mailable->envelope()->subject,
                'body' => $customMailable->mailable->content()->htmlString,
            ];
        }

        return $simulations;
    }
}
