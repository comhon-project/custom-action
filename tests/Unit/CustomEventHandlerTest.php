<?php

namespace Tests\Unit;

use App\Events\CompanyRegistered;
use App\Models\Company;
use App\Models\User;
use Comhon\CustomAction\CustomEventHandler;
use Comhon\CustomAction\Models\CustomEventListener;
use Tests\SetUpWithModelRegistration;
use Tests\TestCase;

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
        foreach ($listener->eventActions as $action) {
            $action->type = Company::class;
            $action->save();
        }

        $this->expectExceptionMessage('invalid type App\Models\Company');
        $this->handler()->handle($event);
    }
}
