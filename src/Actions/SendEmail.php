<?php

namespace Comhon\CustomAction\Actions;

use Comhon\CustomAction\Bindings\BindingsHelper;
use Comhon\CustomAction\Contracts\HasBindingsInterface;
use Comhon\CustomAction\Facades\CustomActionModelResolver;
use Comhon\CustomAction\Models\ActionLocalizedSettings;
use Comhon\CustomAction\Rules\RuleHelper;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Support\Arr;

class SendEmail extends AbstractSendEmail
{
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

    public static function getLocalizedSettingsSchema(?string $eventClassContext = null): array
    {
        return [
            'subject' => 'required|'.RuleHelper::getRuleName('text_template'),
            'body' => 'required|'.RuleHelper::getRuleName('html_template'),
        ];
    }

    protected function getFrom(array $bindings): ?Address
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

    protected function getRecipients(array $bindings, ?array $recipientTypes = null): array
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

    protected function getSubject(array $bindings, ActionLocalizedSettings $localizedSettings): string
    {
        return $localizedSettings->settings['subject']
            ?? throw new \Exception('localized settings subject is not defined');
    }

    protected function getBody(array $bindings, ActionLocalizedSettings $localizedSettings): string
    {
        return $localizedSettings->settings['body']
            ?? throw new \Exception('localized settings body is not defined');
    }

    protected function getAttachments($bindings, ActionLocalizedSettings $localizedSettings): ?iterable
    {
        if (! isset($this->settingsContainer->settings['attachments'])) {
            return [];
        }

        return collect($this->settingsContainer->settings['attachments'])
            ->map(fn ($property) => Arr::get($bindings, $property))
            ->filter(fn ($path) => $path != null);
    }

    protected function loadStaticMailableEntities(): array
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
}
