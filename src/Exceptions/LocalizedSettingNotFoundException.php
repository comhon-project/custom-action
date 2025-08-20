<?php

namespace Comhon\CustomAction\Exceptions;

use Comhon\CustomAction\Models\Setting;

class LocalizedSettingNotFoundException extends RenderableException
{
    public function __construct(public Setting $setting, public ?string $locale, public ?string $fallbackLocale)
    {
        $setingClass = get_class($setting);
        $locale = $locale !== null ? "for locale '{$locale}' " : '';
        $fallbackLocale = ($fallbackLocale !== null && $fallbackLocale !== $locale)
            ? "and for fallback '{$fallbackLocale}' "
            : '';

        $this->message = "Localized setting {$locale}{$fallbackLocale}not found on $setingClass with id {$setting->id}";
    }
}
