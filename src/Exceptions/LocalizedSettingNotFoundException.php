<?php

namespace Comhon\CustomAction\Exceptions;

use Comhon\CustomAction\Models\Setting;

class LocalizedSettingNotFoundException extends \Exception
{
    public function __construct(public Setting $setting, public ?string $locale)
    {
        $setingClass = get_class($setting);
        $locale = $locale !== null ? "for locale '{$locale}' " : '';
        $this->message = "Localized setting {$locale}not found on $setingClass with id {$setting->id}";
    }
}
