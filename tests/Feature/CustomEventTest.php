<?php

namespace Tests\Feature;

use Comhon\CustomAction\Resolver\ModelResolverContainer;
use Comhon\CustomAction\Tests\SetUpWithModelRegistration;
use Comhon\CustomAction\Tests\Support\Models\User;
use Comhon\CustomAction\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CustomEventTest extends TestCase
{
    use RefreshDatabase;
    use SetUpWithModelRegistration;

    public function testGetEvents()
    {
        $user = User::factory()->hasConsumerAbility()->create();
        $response = $this->actingAs($user)->getJson('custom/events');
        $response->assertJson([
            'data' => [
                [
                    'key' => 'company-registered',
                    'name' => 'company registered',
                ],
            ],
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
                    'send-email',
                    'send-company-email',
                ],
            ],
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
