<?php

namespace Comhon\CustomAction\Actions;

use Comhon\CustomAction\Models\LocalizedSetting;
use Illuminate\Contracts\Translation\HasLocalePreference;

/**
 * @property \Comhon\CustomAction\Models\Setting $setting
 */
trait InteractWithLocalizedSettingsTrait
{
    private $localizedSettingCache = [];

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
        if ($locale instanceof HasLocalePreference) {
            $locale = $locale->preferredLocale();
        } elseif (is_array($locale)) {
            $locale = $locale['locale'] ?? $locale['preferred_locale'] ?? null;
        }
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
        $localizedSetting = $this->findLocalizedSetting($locale, $useCache);
        if (! $localizedSetting) {
            throw new \Exception('Action localized settings not found');
        }

        return $localizedSetting;
    }
}
