<?php

namespace Comhon\CustomAction\Tests\Support;

use Comhon\CustomAction\Actions\SendTemplatedMail;
use Comhon\CustomAction\Contracts\CustomUniqueActionInterface;

class SendCompanyRegistrationMail extends SendTemplatedMail implements CustomUniqueActionInterface
{
    public function getBindingSchema(): array
    {
        return [
            ...parent::getBindingSchema(),
            'company.name' => 'string',
            'logo' => 'file',
        ];
    }
    
    public function getSettingsSchema(): array
    {
        return [
            ...parent::getSettingsSchema(),
            'test' => 'string',
        ];
    }
}
