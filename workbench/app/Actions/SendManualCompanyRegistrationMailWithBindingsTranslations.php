<?php

namespace App\Actions;

use Comhon\CustomAction\Contracts\HasTranslatableBindingsInterface;

class SendManualCompanyRegistrationMailWithBindingsTranslations extends SendManualCompanyRegistrationMail implements HasTranslatableBindingsInterface
{
    public function getTranslatableBindings(): array
    {
        return [
            'company.status' => 'status.',
            'company.languages.*.locale' => 'languages.',
        ];
    }
}
