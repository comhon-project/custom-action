<?php

declare(strict_types=1);

namespace Comhon\CustomAction\Actions;

use Comhon\CustomAction\Contracts\CustomActionInterface;
use Comhon\CustomAction\Contracts\HasContextInterface;
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
use Illuminate\Support\Facades\Mail;

abstract class AbstractSendEmail implements CustomActionInterface, HasContextInterface
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
    abstract protected function getFrom(array $context): ?Address;

    /**
     * Get email recipents
     *
     * @return array{to: array, cc: array, bcc: array}
     */
    abstract protected function getRecipients(array $context, ?array $recipientTypes = null): array;

    /**
     * Get email subject.
     *
     * returned subject can be a text template that will be processed to do some replacements
     */
    abstract protected function getSubject(array $context, LocalizedSetting $localizedSetting): string;

    /**
     * Get email body
     *
     * returned body can be a html template that will be processed to do some replacements
     */
    abstract protected function getBody(array $context, LocalizedSetting $localizedSetting): string;

    /**
     * Get email attachements
     */
    abstract protected function getAttachments(array $context, LocalizedSetting $localizedSetting): ?iterable;

    /**
     * Get action context schema.
     *
     * Common context + recipient specific context
     */
    final public static function getContextSchema(): array
    {
        return [
            ...static::getCommonContextSchema() ?? [],
            'to' => RuleHelper::getRuleName('is').':mailable-entity',
            'default_timezone' => 'string',
            'preferred_timezone' => 'string',
        ];
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

    public function getContext(): array
    {
        return [];
    }

    final public function handle()
    {
        $localizedMailInfos = [];
        $validatedContext = $this->getAllValidatedContext(true);
        $validatedReceivers = $this->getRecipients($validatedContext, ['to']);

        // we use not validated and not localized context to find recipients.
        // by using not validating context, we keep original object instances.
        // (usefull for models that implement MailableEntityInterface)
        $context = $this->getAllContext();

        $from = $this->getFrom($context);
        $recipients = $this->getRecipients($context);
        $tos = $recipients['to'] ?? null;

        if (empty($tos)) {
            throw new SendEmailActionException($this->getSetting(), 'there is no mail recipients defined');
        }

        foreach ($tos as $index => $to) {
            $localizedSetting = $this->getLocalizedSettingOrFail($to);
            $locale = $localizedSetting->locale;
            $localizedMailInfos[$locale] ??= $this->getLocalizedMailInfos($localizedSetting, $validatedContext, $from);
            $mailInfos = &$localizedMailInfos[$locale];

            $mailInfos['context']['to'] = $to instanceof MailableEntityInterface
                ? $to->getExposableValues()
                : $validatedReceivers['to'][$index];
            $preferredTimezone = $to instanceof HasTimezonePreferenceInterface
                ? $to->preferredTimezone()
                : null;

            $to = $this->normalizeAddress($to);

            $pendingMail = Mail::to($to);

            if ($recipients['cc'] ?? null) {
                $pendingMail->cc($recipients['cc']);
            }
            if ($recipients['bcc'] ?? null) {
                $pendingMail->bcc($recipients['bcc']);
            }

            $sendMethod = $this->sendAsynchronously ? 'queue' : 'send';
            $pendingMail->$sendMethod(
                new Custom($mailInfos['mail'], $mailInfos['context'], $locale, null, $preferredTimezone)
            );
        }
    }

    protected function getLocalizedMailInfos(LocalizedSetting $localizedSetting, array $context, ?Address $from)
    {
        return [
            'context' => $context,
            'mail' => [
                'from' => $from,
                'subject' => $this->getSubject($context, $localizedSetting),
                'body' => $this->getBody($context, $localizedSetting),
                'attachments' => $this->getAttachments($context, $localizedSetting),
            ],
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
