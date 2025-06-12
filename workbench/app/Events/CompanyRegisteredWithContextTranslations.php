<?php

namespace App\Events;

use App\Exceptions\TestRenderableException;
use App\Models\Company;
use App\Models\User;
use Comhon\CustomAction\Contracts\FakableInterface;
use Comhon\CustomAction\Contracts\HasFakeStateInterface;
use Comhon\CustomAction\Contracts\HasTranslatableContextInterface;

class CompanyRegisteredWithContextTranslations extends CompanyRegistered implements FakableInterface, HasFakeStateInterface, HasTranslatableContextInterface
{
    public static function getTranslatableContext(): array
    {
        return [
            'company.status' => fn ($value, $locale) => __('status.'.$value, [], $locale),
            'company.languages.*.locale' => 'languages.',
        ];
    }

    public static function fake(?array $state = null): static
    {
        $companyState = [];
        if (! empty($state)) {
            $companyState['status'] = '';
            foreach ($state as $value) {
                if (is_array($value) && ($value['status'] ?? null) == 1000) {
                    throw new TestRenderableException('message');
                }
                $companyState['status'] .= '-'.(is_array($value)
                    ? collect($value)->map(fn ($value, $key) => "{$key}_{$value}")->implode('')
                    : $value);
            }
        }

        return new static(Company::factory($companyState)->create(), User::factory()->create());
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
