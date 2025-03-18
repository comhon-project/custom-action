<?php

namespace App\Events;

use Comhon\CustomAction\Contracts\HasTranslatableBindingsInterface;

class CompanyRegisteredWithBindingsTranslations extends CompanyRegistered implements HasTranslatableBindingsInterface
{
    public static function getTranslatableBindings(): array
    {
        return [
            'company.status' => fn ($value, $locale) => __('status.'.$value, [], $locale),
            'company.languages.*.locale' => 'languages.',
        ];
    }
}
