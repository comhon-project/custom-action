<?php

namespace Comhon\CustomAction\Actions;

use Comhon\CustomAction\Context\ContextHelper;
use Comhon\CustomAction\Contracts\CallableFromEventInterface;
use Comhon\CustomAction\Exceptions\SendEmailActionException;
use Comhon\CustomAction\Facades\CustomActionModelResolver;
use Comhon\CustomAction\Models\LocalizedSetting;
use Comhon\CustomAction\Rules\RuleHelper;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Support\Arr;

class SendAutomaticEmail extends AbstractSendEmail implements CallableFromEventInterface
{
    use CallableFromEventTrait;

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

        $contextSchema = ContextHelper::mergeContextSchemas([$eventClassContext, static::class]);
        foreach (static::getContextKeysIgnoredForScopedSetting() as $contextKey) {
            unset($contextSchema[$contextKey]);
        }

        if (count($contextSchema)) {
            $typeBySettingKey = [
                'attachments.*' => 'stored-file',
                'from.context.mailable' => 'mailable-entity',
                'from.context.email' => 'email',
            ];
            foreach (static::RECIPIENT_TYPES as $recipientType) {
                $typeBySettingKey["recipients.{$recipientType}.context.mailables.*"] = 'mailable-entity';
                $typeBySettingKey["recipients.{$recipientType}.context.emails.*"] = 'email';
            }
            $rules = ContextHelper::getContextKeyEnumRuleAccordingType($typeBySettingKey, $contextSchema, true);
            $schema = array_merge($schema, $rules);
        }

        return $schema;
    }

    public static function getLocalizedSettingsSchema(?string $eventClassContext = null): array
    {
        return [
            'subject' => 'required|'.RuleHelper::getRuleName('text_template'),
            'body' => 'required|'.RuleHelper::getRuleName('html_template'),
        ];
    }

    protected function getFrom(array $context): ?Address
    {
        $froms = [];
        $settingsFrom = $this->getSetting()->settings['from'] ?? null;
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
            $contextKey = $settingsFrom['context'][$key] ?? null;
            if ($contextKey) {
                $contextFroms = data_get($context, $contextKey);
                $contextFroms = str_contains($contextKey, '*') ? $contextFroms : [$contextFroms];
                foreach ($contextFroms as $value) {
                    if ($value) {
                        $froms[] = $value;
                    }
                }
            }
        }
        if (count($froms) > 1) {
            throw new SendEmailActionException($this->getSetting(), "several 'from' defined");
        }

        return count($froms) ? $this->normalizeAddress($froms[0]) : null;
    }

    protected function getRecipients(array $context, ?array $recipientTypes = null): array
    {
        $recipients = [];
        $settingsRecipients = $this->getSetting()->settings['recipients'] ?? null;
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
                $contextKeys = $settingsRecipients[$recipientType]['context'][$key] ?? null;
                if ($contextKeys) {
                    foreach ($contextKeys as $contextKey) {
                        $contextRecipents = data_get($context, $contextKey);
                        $contextRecipents = str_contains($contextKey, '*') ? $contextRecipents : [$contextRecipents];
                        foreach ($contextRecipents as $recipient) {
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
        foreach (array_diff(static::RECIPIENT_TYPES, ['to']) as $recipientType) {
            if ($recipients[$recipientType] ?? null) {
                $recipients[$recipientType] = $this->normalizeAddresses($recipients[$recipientType]);
            }
        }

        return $recipients;
    }

    protected function getSubject(array $context, LocalizedSetting $localizedSetting): string
    {
        return $localizedSetting->settings['subject']
            ?? throw new SendEmailActionException($this->getSetting(), 'localized settings subject is not defined');
    }

    protected function getBody(array $context, LocalizedSetting $localizedSetting): string
    {
        return $localizedSetting->settings['body']
            ?? throw new SendEmailActionException($this->getSetting(), 'localized settings body is not defined');
    }

    protected function getAttachments($context, LocalizedSetting $localizedSetting): ?iterable
    {
        if (! isset($this->getSetting()->settings['attachments'])) {
            return [];
        }

        return collect($this->getSetting()->settings['attachments'])
            ->map(fn ($property) => Arr::get($context, $property))
            ->filter(fn ($path) => $path != null);
    }

    protected function loadStaticMailableEntities(): array
    {
        $recipients = $this->getSetting()->settings['recipients'] ?? null;
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
            $mailable = $this->getSetting()->settings['from']['static']['mailable'] ?? null;
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
}
