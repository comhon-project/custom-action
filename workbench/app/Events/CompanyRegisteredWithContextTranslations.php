<?php

namespace App\Events;

use Comhon\CustomAction\Contracts\HasTranslatableContextInterface;

class CompanyRegisteredWithContextTranslations extends CompanyRegistered implements HasTranslatableContextInterface
{
    public static function getTranslatableContext(): array
    {
        return [
            'company.status' => fn ($value, $locale) => __('status.'.$value, [], $locale),
            'company.languages.*.locale' => 'languages.',
        ];
    }
}
