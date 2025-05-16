<?php

namespace App\Events;

use App\Models\Company;
use App\Models\User;
use Comhon\CustomAction\Contracts\FakableInterface;
use Comhon\CustomAction\Contracts\HasTranslatableContextInterface;

class CompanyRegisteredWithContextTranslations extends CompanyRegistered implements FakableInterface, HasTranslatableContextInterface
{
    public static function getTranslatableContext(): array
    {
        return [
            'company.status' => fn ($value, $locale) => __('status.'.$value, [], $locale),
            'company.languages.*.locale' => 'languages.',
        ];
    }

    public static function fake(): static
    {
        return new static(Company::factory()->create(), User::factory()->create());
    }
}
