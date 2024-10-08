<?php

namespace Comhon\CustomAction\Actions;

use Comhon\CustomAction\Bindings\BindingsHelper;
use Comhon\CustomAction\Contracts\BindingsContainerInterface;
use Comhon\CustomAction\Contracts\CustomActionInterface;
use Comhon\CustomAction\Contracts\HasBindingsInterface;
use Comhon\CustomAction\Contracts\HasTimezonePreferenceInterface;
use Comhon\CustomAction\Contracts\MailableEntityInterface;
use Comhon\CustomAction\Facades\CustomActionModelResolver;
use Comhon\CustomAction\Mail\Custom;
use Comhon\CustomAction\Models\ActionLocalizedSettings;
use Comhon\CustomAction\Models\ActionSettingsContainer;
use Comhon\CustomAction\Rules\RuleHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Mail;

class SendTemplatedMail implements CustomActionInterface, HasBindingsInterface
{
    use Dispatchable,
        InteractsWithQueue,
        InteractWithBindingsTrait,
        InteractWithLocalizedSettingsTrait,
        Queueable,
        SerializesModels;

    const RECIPIENT_TYPES = ['to', 'cc', 'bcc'];

    /**
     * @param  mixed  $to  force the email recipient(s) and ignore recipients defined in settings.
     */
    public function __construct(
        private ActionSettingsContainer $settingsContainer,
        private ?BindingsContainerInterface $bindingsContainer = null,
        private mixed $to = null,
    ) {
        //
    }

    /**
     * Indicates if each mail should be sent asynchronously.
     *
     * @var bool
     */
    protected $sendAsynchronously = false;

    /**
     * Get action settings schema
     */
    public static function getSettingsSchema(?string $eventClassContext = null): array
    {
        $schema = [
            'from.static.mailable' => RuleHelper::getRuleName('model_reference').':mailable-entity,from',
            'from.static.email' => 'email',
        ];
        foreach (static::RECIPIENT_TYPES as $recipientType) {
            $schema["recipients.{$recipientType}.static.mailables"] = 'array';
            $schema["recipients.{$recipientType}.static.mailables.*"] = RuleHelper::getRuleName('model_reference').':mailable-entity,recipient';
            $schema["recipients.{$recipientType}.static.emails"] = 'array';
            $schema["recipients.{$recipientType}.static.emails.*"] = 'email';
        }
        if ($eventClassContext && is_subclass_of($eventClassContext, HasBindingsInterface::class)) {
            $bindingTypes = [
                'attachments' => 'array:stored-file',
                'from.bindings.mailable' => 'mailable-entity',
                'from.bindings.email' => 'email',
            ];
            foreach (static::RECIPIENT_TYPES as $recipientType) {
                $bindingTypes["recipients.{$recipientType}.bindings.mailables"] = 'array:mailable-entity';
                $bindingTypes["recipients.{$recipientType}.bindings.emails"] = 'array:email';
            }
            $rules = BindingsHelper::getEventBindingRules($eventClassContext, $bindingTypes);
            $schema = array_merge($schema, $rules);
        }

        return $schema;
    }

    /**
     * Get action localized settings schema
     */
    public static function getLocalizedSettingsSchema(?string $eventClassContext = null): array
    {
        return [
            'subject' => 'required|'.RuleHelper::getRuleName('text_template'),
            'body' => 'required|'.RuleHelper::getRuleName('html_template'),
        ];
    }

    /**
     * Get action binding schema.
     *
     * Global bindings + 'to' binding
     */
    final public static function getBindingSchema(): array
    {
        return [
            ...static::getCommonBindingSchema() ?? [],
            'to' => RuleHelper::getRuleName('is').':mailable-entity',
            'default_timezone' => 'string',
            'preferred_timezone' => 'string',
        ];
    }

    /**
     * Get action common binding schema.
     *
     * Common bindings are bindings that are the same for all recipients
     */
    protected static function getCommonBindingSchema(): ?array
    {
        return [];
    }

    public function getBindingValues(?string $locale = null): array
    {
        return [];
    }

    public function getAttachments($bindings, $settings)
    {
        if (! isset($settings['attachments'])) {
            return [];
        }

        return collect($settings['attachments'])
            ->map(fn ($property) => Arr::get($bindings, $property))
            ->filter(fn ($path) => $path != null);
    }

    public function handle()
    {
        $localizedMailInfos = [];

        $validatedReceivers = $this->getRecipients($this->getValidatedBindings(), ['to']);

        // we use not validated and not localized bindings to find recipients.
        // by using not validating bindings, we keep original object instances.
        // (usefull for models that implement MailableEntityInterface)
        $bindings = [
            ...$this->bindingsContainer?->getBindingValues() ?? [],
            ...$this->getBindingValues(),
        ];

        $from = $this->getFrom($bindings);
        $recipients = $this->getRecipients($bindings);
        $tos = $recipients['to'];

        foreach ($tos as $index => $to) {
            $localizedSettings = $this->findActionLocalizedSettingsOrFail($to, true);
            $locale = $localizedSettings->locale;
            $localizedMailInfos[$locale] ??= $this->getLocalizedMailInfos($localizedSettings);
            $mailInfos = &$localizedMailInfos[$locale];

            $mailInfos['bindings']['to'] = $to instanceof MailableEntityInterface
                ? $to->getExposableValues()
                : $validatedReceivers['to'][$index];
            $preferredTimezone = $to instanceof HasTimezonePreferenceInterface
                ? $to->preferredTimezone()
                : null;

            $sendMethod = $this->sendAsynchronously ? 'queue' : 'send';
            $to = $this->normalizeAddress($to);

            $pendingMail = Mail::to($to);

            if ($recipients['cc'] ?? null) {
                $pendingMail->cc($recipients['cc']);
            }
            if ($recipients['bcc'] ?? null) {
                $pendingMail->bcc($recipients['bcc']);
            }
            if ($from) {
                $mailInfos['mail']['from'] = $from;
            }

            $pendingMail->$sendMethod(
                new Custom($mailInfos['mail'], $mailInfos['bindings'], $locale, null, $preferredTimezone)
            );
        }
    }

    private function getFrom(array $bindings)
    {
        $froms = [];
        $settingsFrom = $this->settingsContainer->settings['from'] ?? null;
        if (! $settingsFrom) {
            return null;
        }
        $mailable = $settingsFrom['static']['mailable'] ?? null;
        if ($mailable) {
            $class = CustomActionModelResolver::getClass($mailable['from_type']);
            $froms[] = $class::find($mailable['from_id']);
        }
        $email = $settingsFrom['static']['email'] ?? null;
        if ($email) {
            $froms[] = $email;
        }
        foreach (['mailable', 'email'] as $key) {
            $bindingsKey = $settingsFrom['bindings'][$key] ?? null;
            if ($bindingsKey) {
                foreach (BindingsHelper::getBindingValues($bindings, $bindingsKey) as $binding) {
                    if ($binding) {
                        $froms[] = $binding;
                    }
                }
            }
        }
        if (count($froms) > 1) {
            throw new \Exception("several 'from' defined");
        }

        return count($froms) ? $this->normalizeAddress($froms[0]) : null;
    }

    private function getRecipients(array $bindings, ?array $recipientTypes = null): array
    {
        if ($this->to) {
            return ['to' => is_array($this->to) ? $this->to : [$this->to]];
        }
        $recipients = [];
        $settingsRecipients = $this->settingsContainer->settings['recipients'] ?? null;
        $mailableEntities = $this->loadStaticMailableEntities();

        $recipientTypes ??= static::RECIPIENT_TYPES;

        foreach ($recipientTypes as $recipientType) {
            $mailables = $settingsRecipients[$recipientType]['static']['mailables'] ?? null;
            if ($mailables) {
                foreach ($mailables as $mailable) {
                    $mailableEntity = $mailableEntities[$mailable['recipient_type']][$mailable['recipient_id']] ?? null;
                    if ($mailableEntity) {
                        $recipients[$recipientType] ??= [];
                        $recipients[$recipientType][] = $mailableEntity;
                    }
                }
            }
            $emails = $settingsRecipients[$recipientType]['static']['emails'] ?? null;
            if ($emails) {
                foreach ($emails as $email) {
                    $recipients[$recipientType] ??= [];
                    $recipients[$recipientType][] = ['email' => $email];
                }
            }
            foreach (['mailables', 'emails'] as $key) {
                $bindingsKeys = $settingsRecipients[$recipientType]['bindings'][$key] ?? null;
                if ($bindingsKeys) {
                    foreach ($bindingsKeys as $bindingsKey) {
                        foreach (BindingsHelper::getBindingValues($bindings, $bindingsKey) as $recipient) {
                            if ($recipient) {
                                $recipients[$recipientType] ??= [];
                                $recipients[$recipientType][] = is_string($recipient)
                                    ? ['email' => $recipient]
                                    : $recipient;
                            }
                        }
                    }
                }
            }
        }
        if (empty($recipients['to'] ?? null)) {
            throw new \Exception('there is no mail recipients defined');
        }
        foreach (array_diff(static::RECIPIENT_TYPES, ['to']) as $recipientType) {
            if ($recipients[$recipientType] ?? null) {
                $recipients[$recipientType] = $this->normalizeAddresses($recipients[$recipientType]);
            }
        }

        return $recipients;
    }

    private function loadStaticMailableEntities(): array
    {
        $recipients = $this->settingsContainer->settings['recipients'] ?? null;
        $mailableEntities = [];
        $mailableIds = [];

        if ($recipients) {
            foreach (static::RECIPIENT_TYPES as $recipientType) {
                $mailables = $recipients[$recipientType]['static']['mailables'] ?? null;
                if ($mailables) {
                    foreach ($mailables as $mailable) {
                        $mailableIds[$mailable['recipient_type']] ??= [];
                        $mailableIds[$mailable['recipient_type']][] = $mailable['recipient_id'];
                    }
                }
            }
            $mailable = $this->settingsContainer->settings['from']['static']['mailable'] ?? null;
            if ($mailable) {
                $mailableIds[$mailable['from_type']] ??= [];
                $mailableIds[$mailable['from_type']][] = $mailable['from_id'];
            }

            foreach ($mailableIds as $uniqueName => $ids) {
                $class = CustomActionModelResolver::getClass($uniqueName);
                $mailableEntities[$uniqueName] = $class::find($ids)->keyBy('id')->all();
            }
        }

        return $mailableEntities;
    }

    private function getLocalizedMailInfos(ActionLocalizedSettings $localizedSettings)
    {
        $bindings = $this->getValidatedBindings($localizedSettings->locale, true);

        return [
            'bindings' => $bindings,
            'mail' => [
                ...$localizedSettings->settings,
                'attachments' => $this->getAttachments($bindings, $this->settingsContainer->settings),
            ],
        ];
    }

    private function normalizeAddresses($values)
    {
        $addresses = [];
        foreach ($values as $value) {
            $addresses[] = $this->normalizeAddress($value);
        }

        return $addresses;
    }

    private function normalizeAddress($value): Address
    {
        if (is_string($value)) {
            return new Address($value);
        } elseif (is_array($value)) {
            return new Address($value['email'], $value['name'] ?? null);
        } elseif ($value instanceof MailableEntityInterface) {
            return new Address($value->getEmail(), $value->getEmailName());
        } elseif (is_object($value)) {
            return new Address($value->email, $value->name ?? null);
        }
    }
}
