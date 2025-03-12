<?php

namespace App\Events;

use Comhon\CustomAction\Contracts\HasTranslatableBindingsInterface;

class CompanyRegisteredWithBindingsTranslations extends CompanyRegistered implements HasTranslatableBindingsInterface
{
    public function getTranslatableBindings(): array
    {
        return [
            'company.status' => 'status.',
            'company.languages.*.locale' => 'languages.',
        ];
    }
}
