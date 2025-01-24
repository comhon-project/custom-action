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

    public function test_handle_without_receiver()
    {
        ManualAction::factory()->sendMailRegistrationCompany([], false, false)->create();

        $this->expectExceptionMessage('there is no mail recipients defined');
        SendCompanyRegistrationMail::handleManual(new BindingsContainer([]));
    }

    public function test_handle_without_bindings_container()
    {
        ManualAction::factory()->sendMailRegistrationCompany([], false, false)->create();

        $this->expectExceptionMessage('there is no mail recipients defined');
        SendCompanyRegistrationMail::handleManual();
    }
}
