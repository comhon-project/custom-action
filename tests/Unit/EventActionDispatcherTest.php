<?php

namespace Tests\Unit;

use App\Events\CompanyRegistered;
use App\Models\Company;
use App\Models\User;
use Comhon\CustomAction\EventActionDispatcher;
use Comhon\CustomAction\Models\EventListener;
use Tests\SetUpWithModelRegistration;
use Tests\TestCase;

class EventActionDispatcherTest extends TestCase
{
    use SetUpWithModelRegistration;

    public function handler(): EventActionDispatcher
    {
        return app(EventActionDispatcher::class);
    }

    public function testHandleWithBadEventInstance()
    {
        $listener = EventListener::factory()->genericRegistrationCompany()->create();
        $event = new CompanyRegistered(Company::factory()->create(), User::factory()->create());
        foreach ($listener->eventActions as $action) {
            $action->type = Company::class;
            $action->save();
        }

        $this->expectExceptionMessage('invalid type App\Models\Company');
        $this->handler()->handle($event);
    }
}
