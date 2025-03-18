<?php

namespace App\Actions;

use Comhon\CustomAction\Contracts\HasTranslatableBindingsInterface;

class SendManualCompanyRegistrationMailWithBindingsTranslations extends SendManualCompanyRegistrationMail implements HasTranslatableBindingsInterface
{
    public static function getTranslatableBindings(): array
    {
        return [
            'company.status' => fn ($value, $locale) => __('status.'.$value, [], $locale),
            'company.languages.*.locale' => 'languages.',
        ];
    }
}
