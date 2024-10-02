<?php

namespace Tests\Feature;

use App\Events\CompanyRegistered;
use App\Models\User;
use Comhon\CustomAction\Facades\CustomActionModelResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\SetUpWithModelRegistrationTrait;
use Tests\TestCase;

class EventDefinitionTest extends TestCase
{
    use RefreshDatabase;
    use SetUpWithModelRegistrationTrait;

    public function testGetEventsSuccess()
    {
        config(['custom-action.events' => [CompanyRegistered::class]]);
        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();
        $response = $this->actingAs($user)->getJson('custom/events');
        $response->assertJson([
            'data' => [
                [
                    'type' => 'company-registered',
                    'name' => 'company registered',
                ],
            ],
        ]);
    }

    public function testGetEventsForbidden()
    {
        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user)->getJson('custom/events')
            ->assertForbidden();
    }

    public function testEventShemaSuccess()
    {
        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();
        $this->actingAs($user)->getJson('custom/events/company-registered/schema')
            ->assertOk()
            ->assertJson([
                'data' => [
                    'binding_schema' => [
                        'company.name' => 'string',
                        'logo' => 'is:stored-file',
                        'user' => 'is:mailable-entity',
                        'user.name' => 'string',
                        'user.email' => 'email',
                    ],
                    'allowed_actions' => [
                        [
                            'type' => 'send-email',
                            'name' => 'send email',
                        ],
                        [
                            'type' => 'send-company-email',
                            'name' => 'send company email',
                        ],
                    ],
                ],
            ]);
    }

    public function testGetEventShemaWithoutBindingsSuccess()
    {
        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();
        $this->actingAs($user)->getJson('custom/events/my-event-without-bindings/schema')
            ->assertOk()
            ->assertJson([
                'data' => [
                    'binding_schema' => [],
                    'allowed_actions' => [
                        [
                            'type' => 'my-action-without-bindings',
                        ],
                    ],
                ],
            ]);
    }

    public function testGetEventShemaNotFound()
    {
        CustomActionModelResolver::register([], true);
        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();
        $response = $this->actingAs($user)->getJson('custom/events/company-registered/schema');
        $response->assertNotFound();
    }

    public function testGetEventShemaForbidden()
    {
        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user)->getJson('custom/events/company-registered/schema')
            ->assertForbidden();
    }
}
