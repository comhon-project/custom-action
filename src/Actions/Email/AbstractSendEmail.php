<?php

declare(strict_types=1);

namespace Comhon\CustomAction\Actions\Email;

use Comhon\CustomAction\Actions\Email\DTOs\CustomMailable;
use Comhon\CustomAction\Actions\Email\DTOs\EmailData;
use Comhon\CustomAction\Actions\Email\Exceptions\SendEmailActionException;
use Comhon\CustomAction\Actions\Email\Mailable\Custom;
use Comhon\CustomAction\Actions\Email\Support\EmailHelper;
use Comhon\CustomAction\Actions\InteractWithContextTrait;
use Comhon\CustomAction\Actions\InteractWithSettingsTrait;
use Comhon\CustomAction\Context\ContextHelper;
use Comhon\CustomAction\Contracts\CustomActionInterface;
use Comhon\CustomAction\Contracts\ExposeContextInterface;
use Comhon\CustomAction\Contracts\HasContextKeysIgnoredForScopedSettingInterface;
use Comhon\CustomAction\Contracts\HasRenderableContextInterface;
use Comhon\CustomAction\Contracts\HasTimezonePreferenceInterface;
use Comhon\CustomAction\Contracts\HasTranslatableContextInterface;
use Comhon\CustomAction\Contracts\MailableEntityInterface;
use Comhon\CustomAction\Contracts\SimulatableInterface;
use Comhon\CustomAction\Models\LocalizedSetting;
use Comhon\CustomAction\Rules\RuleHelper;
use Comhon\TemplateRenderer\RenderContext;
use Comhon\TemplateRenderer\RenderOptions;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
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

    private static string|\Closure|null $defaultTimeZone;

    private static string|\Closure|null $groupedTimeZone;

    /**
     * Indicates if each mail should be sent asynchronously.
     *
     * @var bool
     */
    protected $sendAsynchronously = false;

    /**
     * Indicates if mails should be sent only once the current database transaction is committed
     * (mails are still built immediately, only the sending is deferred).
     *
     * @var bool
     */
    protected $sendAfterCommit = false;

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
     * Send mails only once the current database transaction is committed.
     */
    public function sendAfterCommit(bool $sendAfterCommit = true): static
    {
        $this->sendAfterCommit = $sendAfterCommit;

        return $this;
    }

    public static function registerDefaultTimeZone(string|\Closure|null $timeZone)
    {
        static::$defaultTimeZone = $timeZone;
    }

    public static function registerGroupedTimeZone(string|\Closure|null $timeZone)
    {
        static::$groupedTimeZone = $timeZone;
    }

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
        if (! isset(static::$groupedTimeZone)) {
            return $this->getDefaultTimezone();
        }

        return static::$groupedTimeZone instanceof \Closure
            ? (static::$groupedTimeZone)()
            : static::$groupedTimeZone;
    }

    /**
     * Determine the default timezone to use during template rendering.
     */
    protected function getDefaultTimezone(?string $preferredTimezone = null): string
    {
        if (! isset(static::$defaultTimeZone)) {
            return config('app.timezone');
        }

        return static::$defaultTimeZone instanceof \Closure
            ? (static::$defaultTimeZone)($preferredTimezone)
            : static::$defaultTimeZone;
    }

    private function buildEmailDataConainer(): EmailData
    {
        return new EmailData(
            $this->getExposedValidatedContext(false),
            $this->getRecipients(),
            $this->getFrom(),
        );
    }

    /**
     * Make the replacements closure, called at render time once the locale and timezones are resolved.
     *
     * The closure is static and only captures the context and class names,
     * so it can be serialized with the mail when it is queued.
     *
     * @param  class-string[]  $contextClasses
     */
    private static function makeReplacements(array $context, array $contextClasses): \Closure
    {
        $renderableClasses = array_values(array_filter(
            $contextClasses,
            fn ($class) => is_subclass_of($class, HasRenderableContextInterface::class),
        ));
        $translatableClasses = array_values(array_filter(
            $contextClasses,
            fn ($class) => is_subclass_of($class, HasTranslatableContextInterface::class),
        ));

        return static function (RenderContext $renderContext) use ($context, $renderableClasses, $translatableClasses): array {
            foreach ($renderableClasses as $class) {
                $context = $class::getRenderableContext($context, $renderContext);
            }
            foreach ($translatableClasses as $class) {
                ContextHelper::setTranslationValues($context, $class::getTranslatableContext());
            }

            return $context;
        };
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

    /**
     * @param  bool  $prepare  if true, the mail is fully built now (templates rendered, attachments resolved)
     */
    private function buildCustomMailable(EmailData $emailData, $to, bool $prepare = false): CustomMailable
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
        if (! isset($emailData->localizedMailInfosCache[$locale])) {
            $mailInfos = $this->getLocalizedMailInfos($localizedSetting, $emailData->from);
            if ($prepare) {
                // resolved once per locale, so attachments are loaded once for all mails
                $mailInfos['attachments'] = EmailHelper::resolveAttachments($mailInfos['attachments'] ?? []);
            }
            $emailData->localizedMailInfosCache[$locale] = $mailInfos;
        }

        $mailable = new Custom(
            $emailData->localizedMailInfosCache[$locale],
            self::makeReplacements($emailData->context, array_map(get_class(...), $this->getContextObjects())),
            new RenderOptions(
                locale: $locale,
                timezone: $this->getDefaultTimezone($preferredTimezone),
                preferredTimezone: $preferredTimezone,
            ),
        );
        if ($prepare) {
            $mailable->prerender();
        }

        return new CustomMailable($mailable, $normalizedTo);
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

    public function handle()
    {
        $sendMethod = $this->sendAsynchronously ? 'queue' : 'send';
        $emailData = $this->buildEmailDataConainer();
        $cc = EmailHelper::normalizeAddresses($emailData->cc);
        $bcc = EmailHelper::normalizeAddresses($emailData->bcc);

        // asynchronous mails are rendered by the queue worker
        $prepare = ! $this->sendAsynchronously;
        if ($prepare) {
            EmailHelper::validateAddresses([...Arr::wrap($emailData->from), ...$cc, ...$bcc]);
        }

        foreach ($this->getTos($emailData) as $to) {
            $customMailable = $this->buildCustomMailable($emailData, $to, $prepare);
            if ($prepare) {
                EmailHelper::validateAddresses(Arr::wrap($customMailable->to));
            }

            $send = fn () => Mail::to($customMailable->to)
                ->cc($cc)
                ->bcc($bcc)
                ->$sendMethod($customMailable->mailable);

            if ($this->sendAfterCommit) {
                DB::afterCommit($send);
            } else {
                $send();
            }
        }
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
