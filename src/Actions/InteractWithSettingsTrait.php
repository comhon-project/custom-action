<?php

namespace Comhon\CustomAction\Actions;

use Comhon\CustomAction\ActionSettings\SettingSelector;
use Comhon\CustomAction\Exceptions\LocalizedSettingNotFoundException;
use Comhon\CustomAction\Models\LocalizedSetting;
use Comhon\CustomAction\Models\Setting;
use Illuminate\Contracts\Translation\HasLocalePreference;

/**
 * @property \Comhon\CustomAction\Models\Action $action
 */
trait InteractWithSettingsTrait
{
    protected Setting $setting;

    private array $localizedSettingCache = [];

    public function getSetting(): Setting
    {
        if (! isset($this->setting)) {
            $bindings = $this->getAllBindings(null, true);
            $this->setting = SettingSelector::select($this->action, $bindings);
        }

        return $this->setting;
    }

    protected function getLocaleString(HasLocalePreference|array|string|null $locale): ?string
    {
        if ($locale instanceof HasLocalePreference) {
            $locale = $locale->preferredLocale();
        } elseif (is_array($locale)) {
            $locale = $locale['locale'] ?? $locale['preferred_locale'] ?? null;
        }

        return $locale;
    }

    /**
     * Get action localized settings according given locale
     *
     * If there is no localized settings found with given locale,
     * it try to find localized settings with fallback locales defined on your app
     *
     * @param  bool  $useCache  if true, cache bindings for the action instance,
     *                          and get value from it if exists.
     */
    public function getLocalizedSetting(
        HasLocalePreference|array|string|null $locale = null,
        bool $useCache = false
    ): ?LocalizedSetting {
        $locale = $this->getLocaleString($locale);
        if ($useCache && isset($this->localizedSettingCache[$locale])) {
            return $this->localizedSettingCache[$locale];
        }
        $localizedSetting = $this->getSetting()->getLocalizedSettings($locale);

        if ($useCache) {
            $this->localizedSettingCache[$locale] = $localizedSetting;
        }

        return $localizedSetting;
    }

    /**
     * Get action localized settings according given locale
     *
     * If there is no localized settings found with given locale,
     * it try to find localized settings with fallback locales defined on your app.
     *
     * throw an exception if there is no localized settings found
     *
     * @param  bool  $useCache  if true, cache bindings for the action instance,
     *                          and get value from it if exists.
     */
    public function getLocalizedSettingOrFail(
        HasLocalePreference|array|string|null $locale = null,
        bool $useCache = false
    ): LocalizedSetting {
        return $this->getLocalizedSetting($locale, $useCache)
            ?? throw new LocalizedSettingNotFoundException($this->getSetting(), $this->getLocaleString($locale));
    }
}
