<?php

namespace Tests\Feature;

use App\Models\User;
use Comhon\CustomAction\Facades\CustomActionModelResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\SetUpWithModelRegistrationTrait;
use Tests\TestCase;

class EventDefinitionTest extends TestCase
{
    use RefreshDatabase;
    use SetUpWithModelRegistrationTrait;

    public function test_get_events_success()
    {
        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();
        $response = $this->actingAs($user)->getJson('custom/events');
        $response->assertJson([
            'data' => [
                'company-registered',
            ],
        ]);
    }

    public function test_get_events_forbidden()
    {
        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user)->getJson('custom/events')
            ->assertForbidden();
    }

    public function test_event_shema_success()
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
                        'send-email',
                        'send-company-email',
                    ],
                ],
            ]);
    }

    public function test_get_event_shema_without_bindings_success()
    {
        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();
        $this->actingAs($user)->getJson('custom/events/my-event-without-bindings/schema')
            ->assertOk()
            ->assertJson([
                'data' => [
                    'binding_schema' => [],
                    'allowed_actions' => [
                        'my-action-without-bindings',
                    ],
                ],
            ]);
    }

    public function test_get_event_shema_not_found()
    {
        CustomActionModelResolver::register([], true);
        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();
        $response = $this->actingAs($user)->getJson('custom/events/company-registered/schema');
        $response->assertNotFound();
    }

    public function test_get_event_shema_forbidden()
    {
        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user)->getJson('custom/events/company-registered/schema')
            ->assertForbidden();
    }
}
