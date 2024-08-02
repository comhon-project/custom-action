<?php

namespace Comhon\CustomAction\Actions;

use Comhon\CustomAction\Contracts\BindingsContainerInterface;
use Comhon\CustomAction\Contracts\CustomActionInterface;
use Comhon\CustomAction\Contracts\HasBindingsInterface;
use Comhon\CustomAction\Contracts\HasTimezonePreferenceInterface;
use Comhon\CustomAction\Facades\BindingsValidator;
use Comhon\CustomAction\Facades\CustomActionModelResolver;
use Comhon\CustomAction\Mail\Custom;
use Comhon\CustomAction\Models\ActionSettings;
use Comhon\CustomAction\Models\ActionSettingsContainer;
use Comhon\CustomAction\Models\ManualAction;
use Comhon\CustomAction\Rules\RuleHelper;
use Comhon\CustomAction\Support\Bindings;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Mail;

class SendTemplatedMail implements CustomActionInterface, HasBindingsInterface
{
    /**
     * Indicates if the mail should be queued.
     *
     * @var bool
     */
    protected $shouldQueue = false;

    /**
     * Get action settings schema
     */
    public function getSettingsSchema(?string $eventClassContext = null): array
    {
        $schema = [
            'to_receivers' => 'array',
            'to_receivers.*' => RuleHelper::getRuleName('model_reference').':email-receiver,receiver',
            'to_emails' => 'array',
            'to_emails.*' => 'email',
        ];
        if ($eventClassContext && is_subclass_of($eventClassContext, HasBindingsInterface::class)) {
            $bindingTypes = [
                'to_bindings_receivers' => 'array:email-receiver',
                'to_bindings_emails' => 'array:email',
                'attachments' => 'array:stored-file',
            ];
            $rules = Bindings::getEventBindingRules($eventClassContext, $bindingTypes);
            $schema = array_merge($schema, $rules);
        }

        return $schema;
    }

    /**
     * Get action localized settings schema
     */
    public function getLocalizedSettingsSchema(?string $eventClassContext = null): array
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
            ...static::getGlobalBindingSchema() ?? [],
            'to' => RuleHelper::getRuleName('is').':email-receiver',
            'default_timezone' => 'string',
            'preferred_timezone' => 'string',
        ];
    }

    /**
     * Get action binding schema.
     *
     * Global bindings are bindings that are the same for all receivers
     */
    public static function getGlobalBindingSchema(): ?array
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

    /**
     * @param  \Illuminate\Foundation\Auth\User  $to  used only if 'to' is not defined in action settings
     */
    public function handleManual(?BindingsContainerInterface $bindingsContainer = null, ?User $to = null)
    {
        $class = get_class($this);
        $type = CustomActionModelResolver::getUniqueName($class);
        $action = ManualAction::findOrFail($type);

        $this->handle($action->actionSettings, $bindingsContainer, $to);
    }

    public function handle(
        ActionSettings $actionSettings,
        ?BindingsContainerInterface $bindingsContainer = null,
        mixed $to = null,
    ) {
        $localizedMailInfos = [];
        $notLocalizedBindings = [
            ...$bindingsContainer?->getBindingValues() ?? [],
            ...$this->getBindingValues(),
        ];
        $settingsContainer = $actionSettings->getSettingsContainer($notLocalizedBindings);
        $reveivers = $this->getReceivers($settingsContainer, $notLocalizedBindings, $to);

        foreach ($reveivers as $to) {
            $preferredLocale = $to instanceof HasLocalePreference ? $to->preferredLocale() : null;
            $localeKey = $preferredLocale ?? 'undefined';
            $localizedMailInfos[$localeKey] ??= $this->getLocalizedMailInfos(
                $settingsContainer,
                $bindingsContainer,
                $preferredLocale,
            );
            $mailInfos = $localizedMailInfos[$localeKey];
            $usedLocale = $mailInfos['locale'];
            if (! isset($localizedMailInfos[$usedLocale])) {
                $localizedMailInfos[$usedLocale] = $mailInfos;
            }

            $mailInfos['bindings']['to'] = $to;
            $preferredTimezone = $to instanceof HasTimezonePreferenceInterface
                ? $to->preferredTimezone()
                : null;

            $sendMethod = $this->shouldQueue ? 'queue' : 'send';
            Mail::to($to)->$sendMethod(
                new Custom($mailInfos['mail'], $mailInfos['bindings'], $usedLocale, null, $preferredTimezone)
            );
        }
    }

    private function getReceivers(ActionSettingsContainer $settingsContainer, array $bindings, ?User $to): array
    {
        if ($to) {
            return [$to];
        }
        $tos = [];
        if (isset($settingsContainer->settings['to_receivers'])) {
            $toReceivers = collect($settingsContainer->settings['to_receivers'])->groupBy('receiver_type');
            foreach ($toReceivers as $uniqueName => $modelReceivers) {
                $class = CustomActionModelResolver::getClass($uniqueName);
                $receivers = $class::find(collect($modelReceivers)->pluck('receiver_id'));
                foreach ($receivers as $receiver) {
                    $tos[] = $receiver;
                }
            }
        }
        if (isset($settingsContainer->settings['to_emails'])) {
            foreach ($settingsContainer->settings['to_emails'] as $email) {
                $tos[] = ['email' => $email];
            }
        }
        foreach (['to_bindings_receivers', 'to_bindings_emails'] as $key) {
            if (isset($settingsContainer->settings[$key])) {
                $toBindings = $settingsContainer->settings[$key];
                foreach ($toBindings as $toBinding) {
                    foreach (Bindings::getBindingValues($bindings, $toBinding) as $to) {
                        if ($to) {
                            $tos[] = is_string($to) ? ['email' => $to] : $to;
                        }
                    }
                }
            }
        }
        if (empty($tos)) {
            throw new \Exception('there is no mail receiver defined');
        }

        return $tos;
    }

    private function getLocalizedMailInfos(
        ActionSettingsContainer $settingsContainer,
        ?BindingsContainerInterface $bindingsContainer,
        ?string $locale,
    ) {
        $localizedSettings = $settingsContainer->getLocalizedSettings($locale);
        if (! $localizedSettings) {
            throw new \Exception('localized mail values not found');
        }
        $usedLocale = $localizedSettings->locale;

        $bindingsFromContainer = $bindingsContainer?->getBindingValues($usedLocale) ?? [];
        $schemaFromContainer = $bindingsContainer?->getBindingSchema();
        $bindingsFromContainer = $schemaFromContainer !== null
            ? BindingsValidator::getValidatedBindings($bindingsFromContainer, $schemaFromContainer)
            : $bindingsFromContainer;

        $bindingsFromAction = $this->getBindingValues($usedLocale);
        $schemaFromAction = $this->getGlobalBindingSchema();
        $bindingsFromAction = $schemaFromAction !== null
            ? BindingsValidator::getValidatedBindings($bindingsFromAction, $schemaFromAction) : $bindingsFromAction;

        $bindings = [...$bindingsFromContainer ?? [], ...$bindingsFromAction ?? []];

        return [
            'locale' => $usedLocale,
            'bindings' => $bindings,
            'mail' => [
                ...$localizedSettings->settings,
                'attachments' => $this->getAttachments($bindings, $settingsContainer->settings),
            ],
        ];
    }
}
