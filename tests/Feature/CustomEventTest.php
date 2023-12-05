<?php

namespace Tests\Feature;

use Comhon\CustomAction\Actions\SendTemplatedMail;
use Comhon\CustomAction\Resolver\ModelResolverContainer;
use Comhon\CustomAction\Tests\Support\CompanyRegistered;
use Comhon\CustomAction\Tests\Support\SendCompanyRegistrationMail;
use Comhon\CustomAction\Tests\Support\Models\User;
use Comhon\CustomAction\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CustomEventTest extends TestCase
{
    use RefreshDatabase;

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

    public function testGetEvents()
    {
        $user = User::factory()->hasConsumerAbility()->create();
        $response = $this->actingAs($user)->getJson('custom/events');
        $response->assertJson([
            'data' => [
                [
                    'key' => 'company-registered',
                    'name' => 'company registered',
                ]
            ]
        ]);
    }

    public function testEventShemaSuccess()
    {
        $user = User::factory()->hasConsumerAbility()->create();
        $response = $this->actingAs($user)->getJson('custom/events/company-registered/schema');

        $response->assertJson([
            'data' => [
                'binding_schema' => [
                    'company.name' => 'string',
                    'logo' => 'file',
                ],
                'allowed_actions' => [
                    "send-email",
                    "send-company-email",
                ],
            ]
        ]);
    }

    public function testEventShemaNotFound()
    {
        $resolver = app(ModelResolverContainer::class);
        $resolver->register([]);
        $user = User::factory()->hasConsumerAbility()->create();
        $response = $this->actingAs($user)->getJson('custom/events/company-registered/schema');
        $response->assertNotFound();
    }
}
