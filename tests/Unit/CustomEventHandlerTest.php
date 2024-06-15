<?php

namespace Tests\Unit;

use Comhon\CustomAction\CustomEventHandler;
use Comhon\CustomAction\Models\CustomEventListener;
use Comhon\CustomAction\Tests\SetUpWithModelRegistration;
use Comhon\CustomAction\Tests\Support\CompanyRegistered;
use Comhon\CustomAction\Tests\Support\Models\Company;
use Comhon\CustomAction\Tests\Support\Models\User;
use Comhon\CustomAction\Tests\TestCase;

class CustomEventHandlerTest extends TestCase
{
    use SetUpWithModelRegistration;

    public function handler(): CustomEventHandler
    {
        return app(CustomEventHandler::class);
    }

    public function testHandleWithBadEventInstance()
    {
        $listener = CustomEventListener::factory()->genericRegistrationCompany()->create();
        $event = new CompanyRegistered(Company::factory()->create(), User::factory()->create());
        foreach ($listener->actions as $action) {
            $action->type = Company::class;
            $action->save();
        }

        $this->expectExceptionMessage('invalid type Comhon\CustomAction\Tests\Support\Models\Company');
        $this->handler()->handle($event);
    }
}
