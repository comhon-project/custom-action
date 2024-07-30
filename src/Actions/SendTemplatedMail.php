<?php

namespace Comhon\CustomAction\Actions;

use Comhon\CustomAction\Contracts\CustomActionInterface;
use Comhon\CustomAction\Contracts\CustomEventInterface;
use Comhon\CustomAction\Contracts\HasTimezonePreferenceInterface;
use Comhon\CustomAction\Contracts\TriggerableFromEventInterface;
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

class SendTemplatedMail implements CustomActionInterface, TriggerableFromEventInterface
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
        if ($eventClassContext) {
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
    final public function getBindingSchema($withToBindings = true): array
    {
        return [
            ...$this->getGlobalBindingSchema(),
            'to' => RuleHelper::getRuleName('is').':email-receiver',
        ];
    }

    /**
     * Get action binding schema.
     *
     * Global bindings are bindings that are the same for all receivers
     */
    public function getGlobalBindingSchema(): array
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

    public function handleFromEvent(CustomEventInterface $event, ActionSettings $actionSettings)
    {
        $this->handleFromAction(
            $actionSettings,
            $event->getBindingValues(),
            $event->getBindingSchema()
        );
    }

    /**
     * @param  array  $bindings  used to define scope, replacement and attachments
     * @param  \Illuminate\Foundation\Auth\User  $to  used only if 'to' is not defined in action settings
     */
    public function handle(array $bindings, ?User $to = null)
    {
        $class = get_class($this);
        $type = CustomActionModelResolver::getUniqueName($class);
        $action = ManualAction::findOrFail($type);

        $this->handleFromAction($action->actionSettings, $bindings, null, $to);
    }

    /**
     * @param \Comhon\CustomAction\Models\ActionSettings action settings
     * @param  \Illuminate\Foundation\Auth\User  $to  used only if 'to' is not defined in action settings
     * @param  array  $bindings  used to define scope, replacements and attachments
     * @param  array  $allowedBindings  add bindings to allowed bindings defined in current action
     */
    private function handleFromAction(
        ActionSettings $actionSettings,
        ?array $bindings = null,
        ?array $allowedBindings = null,
        mixed $to = null,
    ) {
        $localizedMails = [];
        $settingsContainer = $actionSettings->getSettingsContainer($bindings);
        $reveivers = $this->getReceivers($settingsContainer, $bindings, $to);
        $attachments = $this->getAttachments($bindings, $settingsContainer->settings);
        $bindings = $this->getAuthorizedBindings($bindings, $allowedBindings);

        foreach ($reveivers as $to) {
            $mail = $this->getMailSettings($settingsContainer, $to, $localizedMails);
            $mail['attachments'] = $attachments;

            $sendMethod = $this->shouldQueue ? 'queue' : 'send';
            $preferredTimezone = $to instanceof HasTimezonePreferenceInterface
                ? $to->preferredTimezone()
                : null;

            $bindings['to'] = $to;

            Mail::to($to)->$sendMethod(
                new Custom($mail, $bindings, $mail['__locale__'], null, $preferredTimezone)
            );
        }
    }

    /**
     * get authorized bindings to avoid to expose some potential sensitive values
     *
     * @param  array  $bindings
     */
    private function getAuthorizedBindings($bindings, ?array $allowedBindings = null)
    {
        $allowedBindingsFromAction = $this->getGlobalBindingSchema();
        $allowedBindings = $allowedBindings
            ? array_merge($allowedBindings, $allowedBindingsFromAction)
            : $allowedBindingsFromAction;

        $authorizedBindings = [];
        foreach ($allowedBindings as $key => $value) {
            $path = explode('.', $key);
            $leaf = array_pop($path);
            $currentLevel = &$authorizedBindings;
            $currentValue = $bindings;
            foreach ($path as $property) {
                $currentLevel[$property] ??= [];
                $currentLevel = &$currentLevel[$property];
                $currentValue = $currentValue[$property] ?? null;
            }
            $currentLevel[$leaf] = $currentValue[$leaf] ?? null;
        }

        return $authorizedBindings;
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

    private function getMailSettings(
        ActionSettingsContainer $settingsContainer,
        $to,
        array &$localizedMails
    ) {
        $locale = $to instanceof HasLocalePreference
            ? $to->preferredLocale()
            : null;

        $localeKey = $locale ?? 'undefined';
        $localizedMails[$localeKey] ??= $settingsContainer->getMergedSettings($locale);
        if (! isset($localizedMails[$localeKey]['__locale__'])) {
            throw new \Exception('localized mail values not found');
        }

        return $localizedMails[$localeKey];
    }
}
