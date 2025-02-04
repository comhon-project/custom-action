<?php

namespace Comhon\CustomAction\Actions;

use Comhon\CustomAction\Exceptions\LocalizedSettingNotFoundException;
use Comhon\CustomAction\Models\LocalizedSetting;
use Illuminate\Contracts\Translation\HasLocalePreference;

/**
 * @property \Comhon\CustomAction\Models\Setting $setting
 */
trait InteractWithLocalizedSettingsTrait
{
    private $localizedSettingCache = [];

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
     * Find action localized settings according given locale
     *
     * If there is no localized settings found with given locale,
     * it try to find localized settings with fallback locales defined on your app
     *
     * @param  bool  $useCache  if true, cache bindings for the action instance,
     *                          and get value from it if exists.
     */
    public function findLocalizedSetting(
        HasLocalePreference|array|string|null $locale = null,
        bool $useCache = false
    ): ?LocalizedSetting {
        $locale = $this->getLocaleString($locale);
        if ($useCache && isset($this->localizedSettingCache[$locale])) {
            return $this->localizedSettingCache[$locale];
        }
        $localizedSetting = $this->setting->getLocalizedSettings($locale);

        if ($useCache) {
            $this->localizedSettingCache[$locale] = $localizedSetting;
        }

        return $localizedSetting;
    }

    /**
     * Find action localized settings according given locale
     *
     * If there is no localized settings found with given locale,
     * it try to find localized settings with fallback locales defined on your app.
     *
     * throw an exception if there is no localized settings found
     *
     * @param  bool  $useCache  if true, cache bindings for the action instance,
     *                          and get value from it if exists.
     */
    public function findLocalizedSettingOrFail(
        HasLocalePreference|array|string|null $locale = null,
        bool $useCache = false
    ): LocalizedSetting {
        return $this->findLocalizedSetting($locale, $useCache)
            ?? throw new LocalizedSettingNotFoundException($this->setting, $this->getLocaleString($locale));
    }
}
