<?php

namespace Comhon\CustomAction\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

abstract class ActionSettingsContainer extends Model
{
    public function action(): MorphTo
    {
        return $this->morphTo();
    }

    public function localizedSettings(): MorphMany
    {
        return $this->morphMany(ActionLocalizedSettings::class, 'localizable');
    }

    /**
     * get localized settings according given locale
     */
    public function getLocalizedSettings(?string $locale = null): ?ActionLocalizedSettings
    {
        if ($locale) {
            $localizedSettings = $this->localizedSettings()->where('locale', $locale)->first();
            if ($localizedSettings) {
                return $localizedSettings;
            }
        }
        $appLocale = config('app.locale');
        if ($appLocale !== $locale) {
            $localizedSettings = $this->localizedSettings()->where('locale', $appLocale)->first();
            if ($localizedSettings) {
                return $localizedSettings;
            }
        }
        $fallbackLocale = config('app.fallback_locale');
        if ($fallbackLocale !== $locale && $fallbackLocale !== $appLocale) {
            $localizedSettings = $this->localizedSettings()->where('locale', $fallbackLocale)->first();
            if ($localizedSettings) {
                return $localizedSettings;
            }
        }

        return null;
    }
}
