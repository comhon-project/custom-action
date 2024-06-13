<?php

namespace Comhon\CustomAction\Actions;

use Comhon\CustomAction\Contracts\CustomActionInterface;
use Comhon\CustomAction\Contracts\CustomEventInterface;
use Comhon\CustomAction\Contracts\CustomUniqueActionInterface;
use Comhon\CustomAction\Contracts\TargetableEventInterface;
use Comhon\CustomAction\Contracts\TriggerableFromEventInterface;
use Comhon\CustomAction\CustomActionRegistrar;
use Comhon\CustomAction\Mail\Custom;
use Comhon\CustomAction\Models\CustomActionSettings;
use Comhon\CustomAction\Resolver\ModelResolverContainer;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;

class SendTemplatedMail implements CustomActionInterface, TriggerableFromEventInterface
{
    public function __construct(private CustomActionRegistrar $registrar, private ModelResolverContainer $resolver)
    {
    }

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

    public function handleFromEvent(
        CustomEventInterface $event,
        CustomActionSettings $customActionSettings,
        ?array $bindings = null
    ) {
        $to = $event instanceof TargetableEventInterface ? $event->target() : null;
        $allowedEventBindings = $event->getBindingSchema();
        $this->handleFromAction($customActionSettings, $bindings, $to, null, $allowedEventBindings);
    }

    /**
     * @param  array  $bindings  used to define scope, replacement and attachments
     * @param  \Illuminate\Foundation\Auth\User  $to  used only if 'to' is not defined in action settings
     * @param  bool  $shouldQueue  override should_queue action setting
     */
    public function handle(array $bindings, ?User $to = null, ?bool $shouldQueue = null)
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
        $this->handleFromAction($customActionSettingss->first(), $bindings, $to, $shouldQueue);
    }

    /**
     * @param \Comhon\CustomAction\Models\CustomActionSettings action settings
     * @param  array  $bindings  used to define scope, replacements and attachments
     * @param  \Illuminate\Foundation\Auth\User  $to  used only if 'to' is not defined in action settings
     * @param  bool  $shouldQueue  override should_queue action setting
     * @param  array  $additionalAllowedBindings  allowed additional bindings
     */
    private function handleFromAction(
        CustomActionSettings $customActionSettings,
        ?array $bindings = null,
        ?User $to = null,
        ?bool $shouldQueue = null,
        ?array $additionalAllowedBindings = null
    ) {
        $shouldQueue ??= $customActionSettings->pivot ? $customActionSettings->pivot->should_queue : false;
        $settingsContainer = $customActionSettings->getSettingsContainer($bindings);
        $attachments = $this->getAttachments($bindings, $settingsContainer->settings);
        $localizedMails = [];

        $allowedBindings = $this->getBindingSchema();
        if ($additionalAllowedBindings) {
            $allowedBindings = array_merge($additionalAllowedBindings, $allowedBindings);
        }

        // we restrict binding to avoid to expose some potential sensitive settings
        $restrictedBindings = [];
        $this->addBindings($allowedBindings, $bindings, $restrictedBindings);

        // build allowed bindings for receiver
        $allowedToBindings = [];
        foreach ($this->getBindingSchema() as $key => $value) {
            if (strpos($key, 'to.') === 0) {
                $allowedToBindings[$key] = $value;
            }
        }
        $userClass = config('custom-action.user_model');
        $mailTo = isset($settingsContainer->settings['to']) ? $userClass::find($settingsContainer->settings['to']) : $to;
        if (empty($mailTo) || ($mailTo instanceof Collection && $mailTo->isEmpty())) {
            throw new \Exception('mail receiver is not defined');
        }
        $mailTos = is_array($mailTo) || $mailTo instanceof Collection ? $mailTo : [$mailTo];
        foreach ($mailTos as $mailTo) {
            $locale = $mailTo instanceof HasLocalePreference ? $mailTo->preferredLocale() : null;
            $localeKey = $locale ?? 'undefined';
            $localizedMails[$localeKey] ??= $settingsContainer->getMergedSettings($locale);
            if (! isset($localizedMails[$localeKey]['__locale__'])) {
                throw new \Exception('localized mail values not found');
            }
            $localeUsed = $localizedMails[$localeKey]['__locale__'];

            // now we add specific binding for receiver
            $toBindings = ['to' => $mailTo];
            $this->addBindings($allowedToBindings, $toBindings, $restrictedBindings);

            $mail = $localizedMails[$localeKey];
            $mail['attachments'] = $attachments;
            $this->send($mail, $mailTo, $restrictedBindings, $shouldQueue, $localeUsed);
        }
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
     * @param  array  $mail  mail informations like subject, body...
     * @param  \Illuminate\Foundation\Auth\User  $to  the mail receiver
     * @param  bool  $shouldQueue  override should_queue action setting
     * @param  string  $locale  locale to use for replacements
     *                          if not specified, locale will be taken
     *                          - from user preferred locale (if user instance of HasLocalePreference)
     *                          - or from default config fallback locale
     */
    public function send(
        array $mail,
        User $to,
        array $replacements,
        bool $shouldQueue = false,
        ?string $defaultLocale = null,
        ?string $defaultTimezone = null
    ) {
        $sendMethod = $shouldQueue ? 'queue' : 'send';
        $defaultLocale ??= $to instanceof HasLocalePreference && $to->preferredLocale()
            ? $to->preferredLocale() : $defaultLocale;
        $preferredTimezone = method_exists($to, 'preferredTimezone') ? $to->preferredTimezone() : null;
        Mail::to($to)->$sendMethod(
            new Custom($mail, $replacements, $defaultLocale, $defaultTimezone, $preferredTimezone)
        );
    }
}
