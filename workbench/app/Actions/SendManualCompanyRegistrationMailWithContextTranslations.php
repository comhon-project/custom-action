<?php

namespace App\Actions;

use App\Models\Company;
use Comhon\CustomAction\Contracts\FakableInterface;
use Comhon\CustomAction\Contracts\HasFakeStateInterface;
use Comhon\CustomAction\Contracts\HasTranslatableContextInterface;
use Comhon\CustomAction\Files\SystemFile;

class SendManualCompanyRegistrationMailWithContextTranslations extends SendManualCompanyRegistrationMail implements FakableInterface, HasFakeStateInterface, HasTranslatableContextInterface
{
    public static function fake(?array $state = null): static
    {
        $companyState = [];
        if (! empty($state)) {
            $companyState['status'] = '';
            foreach ($state as $value) {
                $companyState['status'] .= '-'.(is_array($value)
                    ? collect($value)->map(fn ($value, $key) => "{$key}_{$value}")->implode('')
                    : $value);
            }
        }

        return new static(Company::factory($companyState)->create(), new SystemFile('path'), null);
    }

    public static function getTranslatableContext(): array
    {
        return [
            'company.status' => fn ($value, $locale) => __('status.'.$value, [], $locale),
            'company.languages.*.locale' => 'languages.',
        ];
    }

    public static function getFakeStateSchema(): array
    {
        return [
            'status_1',
            'status_2',
            'status_3',
            'status' => 'integer|min:10',
        ];
    }
}
