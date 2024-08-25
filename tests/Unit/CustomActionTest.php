<?php

namespace Tests\Unit;

use App\Actions\SendCompanyRegistrationMail;
use Comhon\CustomAction\Bindings\BindingsContainer;
use Comhon\CustomAction\Models\ManualAction;
use Tests\SetUpWithModelRegistration;
use Tests\TestCase;

class CustomActionTest extends TestCase
{
    use SetUpWithModelRegistration;

    public function testHandleWithoutReceiver()
    {
        ManualAction::factory()->sendMailRegistrationCompany([], false, false)->create();

        $this->expectExceptionMessage('there is no mail receiver defined');
        SendCompanyRegistrationMail::handleManual(new BindingsContainer([]));
    }

    public function testHandleWithoutBindingsContainer()
    {
        ManualAction::factory()->sendMailRegistrationCompany([], false, false)->create();

        $this->expectExceptionMessage('there is no mail receiver defined');
        SendCompanyRegistrationMail::handleManual();
    }
}
