<?php

namespace Tests\Unit;

use App\Actions\SendCompanyRegistrationMail;
use Comhon\CustomAction\Actions\SendTemplatedMail;
use Comhon\CustomAction\Models\CustomUniqueAction;
use Tests\SetUpWithModelRegistration;
use Tests\TestCase;

class CustomActionTest extends TestCase
{
    use SetUpWithModelRegistration;

    private function getSendMailAction(): SendTemplatedMail
    {
        return app(SendTemplatedMail::class);
    }

    private function getSendMailUniqueAction(): SendCompanyRegistrationMail
    {
        return app(SendCompanyRegistrationMail::class);
    }

    public function testHandleFromNotUniqueAction()
    {
        $this->expectExceptionMessage('No query results for model');
        $this->getSendMailAction()->handle([]);
    }

    public function testHandleWithoutReceiver()
    {
        CustomUniqueAction::factory()->sendMailRegistrationCompany([], false, false)->create();

        $this->expectExceptionMessage('there is no mail receiver defined');
        $this->getSendMailUniqueAction()->handle([]);
    }
}
