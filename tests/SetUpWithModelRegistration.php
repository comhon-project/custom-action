<?php

namespace Tests;

use App\Actions\BadAction;
use App\Actions\MyActionWithoutBindings;
use App\Actions\SendCompanyRegistrationMail;
use App\Events\BadEvent;
use App\Events\CompanyRegistered;
use App\Events\MyEventWithoutBindings;
use App\Models\Company;
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
            'company' => Company::class,

            'company-registered' => CompanyRegistered::class,
            'send-email' => SendTemplatedMail::class,
            'queue-email' => QueueTemplatedMail::class,
            'send-company-email' => SendCompanyRegistrationMail::class,

            'my-event-without-bindings' => MyEventWithoutBindings::class,
            'my-action-without-bindings' => MyActionWithoutBindings::class,

            'bad-event' => BadEvent::class,
            'bad-action' => BadAction::class,
        ]);
    }
}
