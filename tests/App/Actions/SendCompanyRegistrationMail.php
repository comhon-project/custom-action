<?php

namespace App\Actions;

use Comhon\CustomAction\Actions\SendTemplatedMail;
use Comhon\CustomAction\Contracts\CustomUniqueActionInterface;
use Comhon\CustomAction\Rules\RuleHelper;

class SendCompanyRegistrationMail extends SendTemplatedMail implements CustomUniqueActionInterface
{
    public function getGlobalBindingSchema(): array
    {
        return [
            'company.name' => 'string',
            'logo' => RuleHelper::getRuleName('is').':stored-file',
        ];
    }

    public function getSettingsSchema(?string $eventClassContext = null): array
    {
        return [
            ...parent::getSettingsSchema(),
            'test' => 'string',
        ];
    }

    /**
     * Get action localized settings schema
     */
    public function getLocalizedSettingsSchema(?string $eventClassContext = null): array
    {
        return [
            ...parent::getLocalizedSettingsSchema(),
            'test_localized' => 'string',
        ];
    }
}
