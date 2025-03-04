<?php

namespace Tests\Unit;

use App\Actions\SendManualCompanyRegistrationMail;
use App\Events\CompanyRegistered;
use App\Models\Company;
use App\Models\User;
use Comhon\CustomAction\Bindings\EventBindingsContainer;
use Comhon\CustomAction\Files\SystemFile;
use Comhon\CustomAction\Models\ManualAction;
use Tests\SetUpWithModelRegistrationTrait;
use Tests\Support\Utils;
use Tests\TestCase;

class CustomActionTest extends TestCase
{
    use SetUpWithModelRegistrationTrait;

    public function test_handle_without_recipient()
    {
        ManualAction::factory()->sendMailRegistrationCompany([], false, false)->create();

        $this->expectExceptionMessage('there is no mail recipients defined');
        SendManualCompanyRegistrationMail::dispatch(
            Company::factory()->create(),
            new SystemFile(Utils::joinPaths(Utils::getTestPath('Data'), 'jc.jpeg')),
            null,
        );
    }

    public function test_get_events_bindings_container()
    {
        $event = new CompanyRegistered(
            Company::factory()->create(),
            User::factory()->create(),
        );
        $container = new EventBindingsContainer($event);

        $this->assertEquals($event, $container->getEvent());
    }
}
