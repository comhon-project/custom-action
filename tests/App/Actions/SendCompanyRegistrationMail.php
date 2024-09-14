<?php

namespace App\Actions;

use Comhon\CustomAction\Actions\HandleManualActionTrait;
use Comhon\CustomAction\Actions\SendTemplatedMail;
use Comhon\CustomAction\Rules\RuleHelper;

class SendCompanyRegistrationMail extends SendTemplatedMail
{
    use HandleManualActionTrait;

    protected static function getCommonBindingSchema(): array
    {
        return [
            'company.name' => 'string',
            'logo' => RuleHelper::getRuleName('is').':stored-file',
        ];
    }

    public static function getSettingsSchema(?string $eventClassContext = null): array
    {
        return [
            ...parent::getSettingsSchema(),
            'test' => 'required|string',
        ];
    }

    /**
     * Get action localized settings schema
     */
    public static function getLocalizedSettingsSchema(?string $eventClassContext = null): array
    {
        return [
            ...parent::getLocalizedSettingsSchema(),
            'test_localized' => 'string',
        ];
    }
}
