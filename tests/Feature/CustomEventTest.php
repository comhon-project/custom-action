<?php

namespace Tests\Feature;

use App\Models\User;
use Comhon\CustomAction\Resolver\ModelResolverContainer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\SetUpWithModelRegistration;
use Tests\TestCase;

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
        $this->actingAs($user)->getJson('custom/events/company-registered/schema')
            ->assertOk()
            ->assertJson([
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
