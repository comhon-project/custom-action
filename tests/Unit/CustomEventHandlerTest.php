<?php

namespace Tests\Unit;

use Comhon\CustomAction\Actions\SendTemplatedMail;
use Comhon\CustomAction\CustomEventHandler;
use Comhon\CustomAction\Models\CustomEventListener;
use Comhon\CustomAction\Resolver\ModelResolverContainer;
use Comhon\CustomAction\Tests\Support\CompanyRegistered;
use Comhon\CustomAction\Tests\Support\Models\Company;
use Comhon\CustomAction\Tests\Support\Models\User;
use Comhon\CustomAction\Tests\Support\SendCompanyRegistrationMail;
use Comhon\CustomAction\Tests\TestCase;

class CustomEventHandlerTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        /** @var ModelResolverContainer $resolver */
        $resolver = app(ModelResolverContainer::class);
        $resolver->register(
            [
                'send-email' => SendTemplatedMail::class,
                'send-company-email' => SendCompanyRegistrationMail::class,
                'company-registered' => CompanyRegistered::class,
            ],
            [
                'custom-unique-action' => ['send-company-email'],
                'custom-generic-action' => ['send-email'],
                'custom-event' => ['company-registered'],
            ]
        );
    }

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
