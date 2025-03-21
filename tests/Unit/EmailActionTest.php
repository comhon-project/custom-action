<?php

namespace Tests\Unit;

use App\Actions\SendManualCompanyRegistrationMail;
use App\Models\Company;
use Comhon\CustomAction\Files\SystemFile;
use Comhon\CustomAction\Models\ManualAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\SetUpWithModelRegistrationTrait;
use Tests\Support\Utils;
use Tests\TestCase;

class EmailActionTest extends TestCase
{
    use RefreshDatabase;
    use SetUpWithModelRegistrationTrait;

    public function test_send_email_without_recipient()
    {
        ManualAction::factory()->sendMailRegistrationCompany([], false, false)->create();

        $this->expectExceptionMessage('there is no mail recipients defined');
        SendManualCompanyRegistrationMail::dispatch(
            Company::factory()->create(),
            new SystemFile(Utils::joinPaths(Utils::getTestPath('Data'), 'jc.jpeg')),
            null,
        );
    }
}
