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
            $bindings = $this->getAllBindings();
            $this->setting = SettingSelector::select($this->getAction(), $bindings);
        }

        return $this->setting;
    }

    public function getLocaleString(HasLocalePreference|array|string|null $locale): ?string
    {
        if ($locale instanceof HasLocalePreference) {
            $locale = $locale->preferredLocale();
        } elseif (is_array($locale)) {
            $locale = $locale['locale'] ?? $locale['preferred_locale'] ?? null;
        }

        return $locale;
    }

    /**
     * Get action localized setting according given locale
     *
     * If there is no localized settings found with given locale :
     * - if a fallback is given, it tries to find localized setting with given fallback locale
     * - if no fallback is given, it tries to find localized setting with application defined locales
     *
     * @param  bool  $useCache  if true, cache bindings for the action instance,
     *                          and get value from it if exists.
     */
    public function getLocalizedSetting(
        HasLocalePreference|array|string|null $locale = null,
        HasLocalePreference|array|string|null $fallbackLocale = null,
        bool $useCache = true
    ): ?LocalizedSetting {
        $locale = $this->getLocaleString($locale);
        $fallbackLocale = $this->getLocaleString($fallbackLocale);
        $locales = $fallbackLocale ? [$locale, $fallbackLocale] : [$locale];

        foreach ($locales as $i => $currentLocale) {
            $cached = $useCache ? ($this->localizedSettingCache[$currentLocale] ?? null) : null;
            if ($useCache && $cached && $cached->locale == $currentLocale) {
                return $cached;
            }
            $localizedSetting = $cached ?? $this->getSetting()->getLocalizedSetting($currentLocale);

            if ($useCache) {
                $this->localizedSettingCache[$currentLocale] = $localizedSetting;
            }

            if ($localizedSetting && $localizedSetting->locale == $currentLocale) {
                return $localizedSetting;
            } elseif ($i == 1 && $localizedSetting) {
                // when there is a given fallback we don't keep application locale fallback
                $localizedSetting = null;
            }
        }

        return $localizedSetting;
    }

    /**
     * @see \Comhon\CustomAction\Actions\InteractWithSettingsTrait::getLocalizedSetting()
     *
     * throw an exception if there is no localized settings found
     */
    public function getLocalizedSettingOrFail(
        HasLocalePreference|array|string|null $locale = null,
        HasLocalePreference|array|string|null $fallbackLocale = null,
        bool $useCache = true
    ): LocalizedSetting {
        return $this->getLocalizedSetting($locale, $fallbackLocale, $useCache)
            ?? throw new LocalizedSettingNotFoundException(
                $this->getSetting(),
                $this->getLocaleString($locale),
                $this->getLocaleString($fallbackLocale)
            );
    }
}
