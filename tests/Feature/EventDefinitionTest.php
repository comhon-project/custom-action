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
                'my-simple-event',
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

    public function test_get_event_schema_with_context_success()
    {
        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();
        $this->actingAs($user)->getJson('custom/events/my-complex-event/schema')
            ->assertOk()
            ->assertJson([
                'data' => [
                    'context_schema' => [
                        'user.id' => 'integer',
                        'user.status' => 'string',
                        'user.translation' => 'string',
                        'user.email' => 'email',
                    ],
                    'translatable_context' => [
                        'user.translation',
                    ],
                    'allowed_actions' => [
                        'complex-event-action',
                    ],
                    'fakable' => true,
                ],
            ]);
    }

    public function test_get_event_schema_without_context_success()
    {
        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();
        $this->actingAs($user)->getJson('custom/events/my-simple-event/schema')
            ->assertOk()
            ->assertJson([
                'data' => [
                    'context_schema' => [],
                    'translatable_context' => [],
                    'allowed_actions' => [
                        'simple-event-action',
                    ],
                    'fakable' => false,
                ],
            ]);
    }

    public function test_get_event_schema_not_found()
    {
        CustomActionModelResolver::register([], true);
        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();
        $response = $this->actingAs($user)->getJson('custom/events/my-simple-event/schema');
        $response->assertNotFound();
    }

    public function test_get_event_schema_forbidden()
    {
        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user)->getJson('custom/events/my-simple-event/schema')
            ->assertForbidden();
    }
}
