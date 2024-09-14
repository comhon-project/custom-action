<?php

namespace Tests\Unit;

use App\Actions\SendCompanyRegistrationMail;
use Comhon\CustomAction\Bindings\BindingsContainer;
use Comhon\CustomAction\Models\ManualAction;
use Tests\SetUpWithModelRegistrationTrait;
use Tests\TestCase;

class CustomActionTest extends TestCase
{
    use SetUpWithModelRegistrationTrait;

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
