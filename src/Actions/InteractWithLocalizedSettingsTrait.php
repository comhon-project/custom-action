<?php

namespace Comhon\CustomAction\Actions;

use Comhon\CustomAction\Models\ActionLocalizedSettings;
use Illuminate\Contracts\Translation\HasLocalePreference;

trait InteractWithLocalizedSettingsTrait
{
    private $localizedSettingsCache = [];

    /**
     * Find action localized settings according given locale
     *
     * If there is no localized settings found with given locale,
     * it try to find localized settings with fallback locales defined on your app
     *
     * @param  bool  $useCache  if true, cache bindings for the action instance,
     *                          and get value from it if exists.
     */
    public function findActionLocalizedSettings(
        HasLocalePreference|array|string|null $locale = null,
        bool $useCache = false
    ): ?ActionLocalizedSettings {
        if ($locale instanceof HasLocalePreference) {
            $locale = $locale->preferredLocale();
        } elseif (is_array($locale)) {
            $locale = $locale['locale'] ?? $locale['preferred_locale'] ?? null;
        }
        if ($useCache && isset($this->localizedSettingsCache[$locale])) {
            return $this->localizedSettingsCache[$locale];
        }
        $localizedSettings = $this->settingsContainer->getLocalizedSettings($locale);

        if ($useCache) {
            $this->localizedSettingsCache[$locale] = $localizedSettings;
        }

        return $localizedSettings;
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
    public function findActionLocalizedSettingsOrFail(
        HasLocalePreference|array|string|null $locale = null,
        bool $useCache = false
    ): ActionLocalizedSettings {
        $localizedSettings = $this->findActionLocalizedSettings($locale, $useCache);
        if (! $localizedSettings) {
            throw new \Exception('Action localized settings not found');
        }

        return $localizedSettings;
    }
}
