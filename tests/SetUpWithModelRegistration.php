<?php

namespace Tests;

use App\Actions\SendCompanyRegistrationMail;
use App\Events\CompanyRegistered;
use App\Models\User;
use Comhon\CustomAction\Actions\QueueTemplatedMail;
use Comhon\CustomAction\Actions\SendTemplatedMail;
use Comhon\CustomAction\Facades\CustomActionModelResolver;

trait SetUpWithModelRegistration
{
    public function setUp(): void
    {
        parent::setUp();

        CustomActionModelResolver::register([
            'user' => User::class,
            'send-email' => SendTemplatedMail::class,
            'queue-email' => QueueTemplatedMail::class,
            'send-company-email' => SendCompanyRegistrationMail::class,
            'company-registered' => CompanyRegistered::class,
        ]);
    }
}
