<?php

namespace App\Actions;

use Comhon\CustomAction\Contracts\HasTranslatableContextInterface;

class SendManualCompanyRegistrationMailWithContextTranslations extends SendManualCompanyRegistrationMail implements HasTranslatableContextInterface
{
    public static function getTranslatableContext(): array
    {
        return [
            'company.status' => fn ($value, $locale) => __('status.'.$value, [], $locale),
            'company.languages.*.locale' => 'languages.',
        ];
    }
}
