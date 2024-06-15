<?php

namespace Tests\Unit;

use Comhon\CustomAction\Actions\SendTemplatedMail;
use Comhon\CustomAction\Contracts\CustomUniqueActionInterface;
use Comhon\CustomAction\Models\CustomActionSettings;
use Comhon\CustomAction\Tests\SetUpWithModelRegistration;
use Comhon\CustomAction\Tests\Support\SendCompanyRegistrationMail;
use Comhon\CustomAction\Tests\TestCase;

class CustomActionTest extends TestCase
{
    use SetUpWithModelRegistration;

    public function getSendMailAction(): SendTemplatedMail
    {
        return app(SendTemplatedMail::class);
    }

    private function getSendMailUniqueAction(): SendCompanyRegistrationMail
    {
        return app(SendCompanyRegistrationMail::class);
    }

    public function testHandleFromNotUniqueAction()
    {
        $this->expectExceptionMessage('must be called from an instance of '.CustomUniqueActionInterface::class);
        $this->getSendMailAction()->handle([]);
    }

    public function testHandleWithoutReceiver()
    {
        CustomActionSettings::factory()->sendMailRegistrationCompany([], false, 'send-company-email', false)->create();

        $this->expectExceptionMessage('mail receiver is not defined');
        $this->getSendMailUniqueAction()->handle([]);
    }
}
