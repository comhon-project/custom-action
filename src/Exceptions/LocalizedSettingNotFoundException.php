<?php

namespace Comhon\CustomAction\Exceptions;

use Comhon\CustomAction\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LocalizedSettingNotFoundException extends \Exception
{
    public function __construct(public Setting $setting, public ?string $locale, public ?string $fallbackLocale)
    {
        $setingClass = get_class($setting);
        $locale = $locale !== null ? "for locale '{$locale}' " : '';
        $fallbackLocale = $fallbackLocale !== null ? "and for fallback '{$locale}' " : '';
        $this->message = "Localized setting {$locale}{$fallbackLocale}not found on $setingClass with id {$setting->id}";
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
        ], 422);
    }
}
