<?php

namespace Comhon\CustomAction\Actions;

use Comhon\CustomAction\Contracts\CustomActionInterface;
use Comhon\CustomAction\Contracts\CustomEventInterface;
use Comhon\CustomAction\Contracts\CustomUniqueActionInterface;
use Comhon\CustomAction\Contracts\HasTimezonePreferenceInterface;
use Comhon\CustomAction\Contracts\TargetableEventInterface;
use Comhon\CustomAction\Contracts\TriggerableFromEventInterface;
use Comhon\CustomAction\CustomActionRegistrar;
use Comhon\CustomAction\Mail\Custom;
use Comhon\CustomAction\Models\ActionSettingsContainer;
use Comhon\CustomAction\Models\CustomActionSettings;
use Comhon\CustomAction\Resolver\ModelResolverContainer;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;

class SendTemplatedMail implements CustomActionInterface, TriggerableFromEventInterface
{
    /**
     * Indicates if the mail should be queued.
     *
     * @var bool
     */
    protected $shouldQueue = false;

    public function __construct(private CustomActionRegistrar $registrar, private ModelResolverContainer $resolver) {}

    /**
     * vefify if action concern a targeted user
     */
    public function hasTargetUser(): bool
    {
        return true;
    }

    /**
     * Get action settings schema
     */
    public function getSettingsSchema(): array
    {
        return [
            'attachments' => 'array:file',
        ];
    }

    /**
     * Get action localized settings schema
     */
    public function getLocalizedSettingsSchema(): array
    {
        return [
            'subject' => 'template',
            'body' => 'template',
        ];
    }

    /**
     * Get action binding schema
     */
    public function getBindingSchema(): array
    {
        return $this->registrar->getTargetBindings();
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

    public function handleFromEvent(CustomEventInterface $event, CustomActionSettings $customActionSettings)
    {
        $this->handleFromAction(
            $customActionSettings,
            $event instanceof TargetableEventInterface ? $event->target() : null,
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
        if (! ($this instanceof CustomUniqueActionInterface)) {
            throw new \Exception('must be called from an instance of '.CustomUniqueActionInterface::class);
        }
        $class = get_class($this);
        $type = $this->resolver->getUniqueName($class);
        $customActionSettingss = CustomActionSettings::where('type', $type)->get();
        if ($customActionSettingss->isEmpty()) {
            throw new \Exception("action settings not set for $class");
        }
        if ($customActionSettingss->count() > 1) {
            throw new \Exception("several '$type' actions found");
        }
        $this->handleFromAction($customActionSettingss->first(), $to, $bindings);
    }

    /**
     * @param \Comhon\CustomAction\Models\CustomActionSettings action settings
     * @param  \Illuminate\Foundation\Auth\User  $to  used only if 'to' is not defined in action settings
     * @param  array  $bindings  used to define scope, replacements and attachments
     * @param  array  $allowedBindings  add bindings to allowed bindings defined in current action
     */
    private function handleFromAction(
        CustomActionSettings $customActionSettings,
        ?User $to = null,
        ?array $bindings = null,
        ?array $allowedBindings = null
    ) {
        $localizedMails = [];
        $settingsContainer = $customActionSettings->getSettingsContainer($bindings);
        $attachments = $this->getAttachments($bindings, $settingsContainer->settings);
        $bindings = $this->getAuthorizedBindings($bindings, $allowedBindings);
        $allowedToBindings = $this->getAllowedToBindings();
        $reveivers = $this->getReceivers($settingsContainer, $to);

        foreach ($reveivers as $to) {
            $mail = $this->getMailSettings($settingsContainer, $to, $localizedMails);
            $mail['attachments'] = $attachments;

            // we add specific bindings for receiver
            $this->addBindings($allowedToBindings, ['to' => $to], $bindings);

            $sendMethod = $this->shouldQueue ? 'queue' : 'send';
            $preferredTimezone = $to instanceof HasTimezonePreferenceInterface
                ? $to->preferredTimezone()
                : null;

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
        $allowedBindingsFromAction = $this->getBindingSchema();
        if ($allowedBindings) {
            $allowedBindingsFromAction = array_merge($allowedBindings, $allowedBindingsFromAction);
        }

        $authorizedBindings = [];
        $this->addBindings($allowedBindingsFromAction, $bindings, $authorizedBindings);

        return $authorizedBindings;
    }

    /**
     * @param  array  $allowedToBindings
     * @param  array  $originalBindings
     * @param  array  $bindings
     */
    private function addBindings($allowedToBindings, $originalBindings, &$bindings)
    {
        foreach ($allowedToBindings as $key => $value) {
            $path = explode('.', $key);
            $leaf = array_pop($path);
            $currentLevel = &$bindings;
            $currentValue = $originalBindings;
            foreach ($path as $property) {
                $currentLevel[$property] ??= [];
                $currentLevel = &$currentLevel[$property];
                $currentValue = $currentValue[$property] ?? null;
            }
            $currentLevel[$leaf] = $currentValue[$leaf] ?? null;
        }
    }

    /**
     * get allowed bindings for receiver
     */
    private function getAllowedToBindings()
    {
        $allowed = [];
        foreach ($this->getBindingSchema() as $key => $value) {
            if (strpos($key, 'to.') === 0) {
                $allowed[$key] = $value;
            }
        }

        return $allowed;
    }

    private function getReceivers(ActionSettingsContainer $settingsContainer, ?User $to)
    {
        $userClass = config('custom-action.user_model');
        $to = isset($settingsContainer->settings['to'])
            ? $userClass::find($settingsContainer->settings['to'])
            : $to;

        if (empty($to) || ($to instanceof Collection && $to->isEmpty())) {
            throw new \Exception('mail receiver is not defined');
        }

        return is_array($to) || $to instanceof Collection ? $to : [$to];
    }

    private function getMailSettings(
        ActionSettingsContainer $settingsContainer,
        User $to,
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
