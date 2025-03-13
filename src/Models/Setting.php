<?php

namespace Comhon\CustomAction\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

abstract class Setting extends Model
{
    public function action(): MorphTo
    {
        return $this->morphTo();
    }

    public function localizedSettings(): MorphMany
    {
        return $this->morphMany(LocalizedSetting::class, 'localizable');
    }

    /**
     * get localized settings according given locale
     */
    public function getLocalizedSetting(?string $locale = null): ?LocalizedSetting
    {
        if ($locale) {
            $localizedSetting = $this->localizedSettings()->where('locale', $locale)->first();
            if ($localizedSetting) {
                return $localizedSetting;
            }
        }
        $appLocale = config('app.locale');
        if ($appLocale !== $locale) {
            $localizedSetting = $this->localizedSettings()->where('locale', $appLocale)->first();
            if ($localizedSetting) {
                return $localizedSetting;
            }
        }
        $fallbackLocale = config('app.fallback_locale');
        if ($fallbackLocale !== $locale && $fallbackLocale !== $appLocale) {
            $localizedSetting = $this->localizedSettings()->where('locale', $fallbackLocale)->first();
            if ($localizedSetting) {
                return $localizedSetting;
            }
        }

        return null;
    }
}
