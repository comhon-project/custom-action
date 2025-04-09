<?php

declare(strict_types=1);

namespace Comhon\CustomAction\Actions;

use Comhon\CustomAction\Contracts\CustomActionInterface;
use Comhon\CustomAction\Contracts\ExposeContextInterface;
use Comhon\CustomAction\Contracts\HasContextKeysIgnoredForScopedSettingInterface;
use Comhon\CustomAction\Contracts\HasTimezonePreferenceInterface;
use Comhon\CustomAction\Contracts\MailableEntityInterface;
use Comhon\CustomAction\Exceptions\SendEmailActionException;
use Comhon\CustomAction\Mail\Custom;
use Comhon\CustomAction\Models\LocalizedSetting;
use Comhon\CustomAction\Rules\RuleHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;

abstract class AbstractSendEmail implements CustomActionInterface, ExposeContextInterface, HasContextKeysIgnoredForScopedSettingInterface
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
     * @return array{to: array, cc: array, bcc: array}
     */
    abstract protected function getRecipients(?array $recipientTypes = null): array;

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
     * Determine which timezone should be used when sending grouped emails.
     */
    protected function getGroupedTimezone(): string
    {
        return config('app.timezone');
    }

    final public function handle()
    {
        $localizedMailInfos = [];
        $context = $this->getExposedValidatedContext(true);
        $from = $this->getFrom();
        $recipients = $this->getRecipients();
        $tos = $recipients['to'] ?? null;

        if (empty($tos)) {
            throw new SendEmailActionException($this->getSetting(), 'there is no mail recipients defined');
        }

        $groupRecipients = $this->shouldGroupRecipients() && count($tos) > 1;
        if ($groupRecipients) {
            $tos = [$tos];
        }

        foreach ($tos as $to) {
            if ($groupRecipients) {
                $localizedSetting = $this->getLocalizedSettingOrFail($this->getGroupedLocale());
                $preferredTimezone = $this->getGroupedTimezone();
                $to = $this->normalizeAddresses($to);
            } else {
                $localizedSetting = $this->getLocalizedSettingOrFail(is_string($to) ? null : $to);

                // we expose 'to' value in email body or subject only if recipient is a MailableEntityInterface
                $context['to'] = $to instanceof MailableEntityInterface
                    ? $to->getExposableValues()
                    : null;

                $preferredTimezone = $to instanceof HasTimezonePreferenceInterface
                    ? $to->preferredTimezone()
                    : null;

                $to = $this->normalizeAddress($to);
            }

            $locale = $localizedSetting->locale;
            $localizedMailInfos[$locale] ??= $this->getLocalizedMailInfos($localizedSetting, $from);

            $pendingMail = Mail::to($to);

            if ($recipients['cc'] ?? null) {
                $pendingMail->cc($recipients['cc']);
            }
            if ($recipients['bcc'] ?? null) {
                $pendingMail->bcc($recipients['bcc']);
            }

            $sendMethod = $this->sendAsynchronously ? 'queue' : 'send';
            $pendingMail->$sendMethod(
                new Custom($localizedMailInfos[$locale], $context, $locale, null, $preferredTimezone)
            );
        }
    }

    protected function getLocalizedMailInfos(LocalizedSetting $localizedSetting, ?Address $from)
    {
        return [
            'from' => $from,
            'subject' => $this->getSubject($localizedSetting),
            'body' => $this->getBody($localizedSetting),
            'attachments' => $this->getAttachments($localizedSetting),
        ];
    }

    protected function normalizeAddresses($values)
    {
        $addresses = [];
        foreach ($values as $value) {
            $addresses[] = $this->normalizeAddress($value);
        }

        return $addresses;
    }

    protected function normalizeAddress($value): Address
    {
        return match (true) {
            is_string($value) => new Address($value),
            is_array($value) => new Address($value['email'], $value['name'] ?? null),
            $value instanceof MailableEntityInterface => new Address($value->getEmail(), $value->getEmailName()),
            is_object($value) => new Address($value->email, $value->name ?? null),
        };
    }
}
