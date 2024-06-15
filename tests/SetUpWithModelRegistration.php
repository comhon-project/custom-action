<?php

namespace Comhon\CustomAction\Tests;

use Comhon\CustomAction\Actions\QueueTemplatedMail;
use Comhon\CustomAction\Actions\SendTemplatedMail;
use Comhon\CustomAction\Resolver\ModelResolverContainer;
use Comhon\CustomAction\Tests\Support\CompanyRegistered;
use Comhon\CustomAction\Tests\Support\SendCompanyRegistrationMail;

trait SetUpWithModelRegistration
{
    public function setUp(): void
    {
        parent::setUp();

        /** @var ModelResolverContainer $resolver */
        $resolver = app(ModelResolverContainer::class);
        $resolver->register(
            [
                'send-email' => SendTemplatedMail::class,
                'queue-email' => QueueTemplatedMail::class,
                'send-company-email' => SendCompanyRegistrationMail::class,
                'company-registered' => CompanyRegistered::class,
            ],
            [
                'custom-unique-action' => ['send-company-email'],
                'custom-generic-action' => ['send-email', 'queue-email'],
                'custom-event' => ['company-registered'],
            ]
        );
    }
}
