<?php

namespace App\Actions;

use App\Models\Company;
use Comhon\CustomAction\Contracts\FakableInterface;
use Comhon\CustomAction\Contracts\HasTranslatableContextInterface;
use Comhon\CustomAction\Files\SystemFile;

class SendManualCompanyRegistrationMailWithContextTranslations extends SendManualCompanyRegistrationMail implements FakableInterface, HasTranslatableContextInterface
{
    public static function fake(): static
    {
        return new static(Company::factory()->create(), new SystemFile('path'), null);
    }

    public static function getTranslatableContext(): array
    {
        return [
            'company.status' => fn ($value, $locale) => __('status.'.$value, [], $locale),
            'company.languages.*.locale' => 'languages.',
        ];
    }
}
