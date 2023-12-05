<?php

namespace Comhon\CustomAction\Models;

use Illuminate\Database\Eloquent\Model;

abstract class ActionSettingsContainer extends Model
{

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function localizedSettings()
    {
        return $this->morphMany(ActionLocalizedSettings::class, 'localizable');
    }

    /**
     * get settings + localized settings according given locale
     * 
     * @param string $locale
     * @return array
     */
    public function getMergedSettings($locale = null)
    {
        $localizedSettings = $this->getLocalizedSettings($locale);
        return $localizedSettings ? array_merge($this->settings, $localizedSettings) : $this->settings;
    }

    /**
     * get localized settings according given locale
     * 
     * @param string $locale
     * @return array
     */
    public function getLocalizedSettings($locale = null)
    {
        $localizedSettings = null;
        $settings = null;
        $usedLocale = null;
        if ($locale) {
            $localizedSettings = $this->localizedSettings()->where('locale', $locale)->first();
            $usedLocale = $locale;
        }
        $appLocale = config('app.locale');
        if (!$localizedSettings && $appLocale !== $locale) {
            $localizedSettings = $this->localizedSettings()->where('locale', $appLocale)->first();
            $usedLocale = $appLocale;
        }
        $fallbackLocale = config('app.fallback_locale');
        if (!$localizedSettings && $fallbackLocale !== $locale && $fallbackLocale !== $appLocale) {
            $localizedSettings = $this->localizedSettings()->where('locale', $fallbackLocale)->first();
            $usedLocale = $fallbackLocale;
        }
        if ($localizedSettings) {
            $settings = $localizedSettings->settings;
            $settings['__locale__'] = $usedLocale;
        }

        return $settings;
    }
}
