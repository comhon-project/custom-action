<?php

namespace Tests\Feature;

use App\Events\MyComplexEvent;
use App\Events\MySimpleEvent;
use App\Models\User;
use Comhon\CustomAction\Models\EventListener;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\SetUpWithModelRegistrationTrait;
use Tests\TestCase;

class EventListenerTest extends TestCase
{
    use RefreshDatabase;
    use SetUpWithModelRegistrationTrait;

    public function test_get_event_listeners_success()
    {
        $eventListener = EventListener::factory()->event(MySimpleEvent::class)->create();
        $eventListener2 = EventListener::factory([
            'scope' => ['user.id' => 1],
        ])->event(MySimpleEvent::class)->create();

        // must not be returned
        EventListener::factory()->event(MyComplexEvent::class)->create();

        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();
        $this->actingAs($user)->getJson('custom/events/my-simple-event/listeners')
            ->assertJsonCount(2, 'data')
            ->assertJson([
                'data' => [
                    [
                        'id' => $eventListener->id,
                        'event' => 'my-simple-event',
                        'name' => 'My Custom Event Listener',
                        'scope' => null,
                    ],
                    [
                        'id' => $eventListener2->id,
                        'event' => 'my-simple-event',
                        'name' => 'My Custom Event Listener',
                        'scope' => [
                            'user.id' => 1,
                        ],
                    ],
                ],
            ]);
    }

    public function test_get_event_listeners_with_filter()
    {
        $eventListener = EventListener::factory(['name' => 'the one'])
            ->event(MySimpleEvent::class)
            ->create();

        EventListener::factory()
            ->event(MySimpleEvent::class)
            ->create();

        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();
        $params = http_build_query(['name' => 'one']);
        $this->actingAs($user)->getJson("custom/events/my-simple-event/listeners?$params")
            ->assertJsonCount(1, 'data')
            ->assertJson([
                'data' => [
                    [
                        'id' => $eventListener->id,
                        'event' => 'my-simple-event',
                        'name' => 'the one',
                        'scope' => null,
                    ],
                ],
            ]);
    }

    public function test_get_event_listeners_with_not_found_event()
    {
        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();
        $response = $this->actingAs($user)->getJson('custom/events/unknown-event/listeners');

        $response->assertNotFound();
    }

    public function test_get_event_listeners_forbidden()
    {
        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user)->getJson('custom/events/my-simple-event/listeners')
            ->assertForbidden();
    }

    public function test_store_event_listeners()
    {
        $data = [
            'event' => 'my-simple-event',
            'scope' => ['user.id' => 1],
            'name' => 'my event listener',
        ];
        $inputs = [
            ...$data,
            'event' => 'foo', // must not be taken in account
        ];
        $this->assertEquals(0, EventListener::count());

        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();
        $response = $this->actingAs($user)->postJson('custom/events/my-simple-event/listeners', $inputs)
            ->assertCreated()
            ->assertJsonStructure(['data' => ['id']])
            ->assertJson([
                'data' => $data,
            ]);

        $eventListener = EventListener::findOrFail($response->json('data.id'));
        $this->assertArraySubset($data, $eventListener->toArray());
    }

    public function test_store_event_listeners_with_not_found_event()
    {
        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();
        $response = $this->actingAs($user)->postJson('custom/events/unkown-event/listeners', []);
        $response->assertNotFound();
    }

    public function test_store_event_listeners_forbidden()
    {
        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user)->postJson('custom/events/my-simple-event/listeners')
            ->assertForbidden();
    }

    public function test_update_event_listener_success()
    {
        $eventListener = EventListener::factory()->event(MySimpleEvent::class)->create();
        $this->assertNull($eventListener->scope);

        $data = [
            'event' => 'my-simple-event',
            'scope' => ['user.id' => 1],
            'name' => 'updated name',
        ];
        $inputs = [
            ...$data,
            'event' => 'foo', // must not be taken in account
        ];

        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();
        $this->actingAs($user)->putJson("custom/event-listeners/$eventListener->id", $inputs)
            ->assertOk()
            ->assertJson([
                'data' => [
                    'id' => $eventListener->id,
                    ...$data,
                ],
            ]);

        $this->assertArraySubset($data, $eventListener->refresh()->toArray());
    }

    public function test_update_event_listener_forbidden()
    {
        $eventListener = EventListener::factory()->event(MySimpleEvent::class)->create();

        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user)->putJson("custom/event-listeners/$eventListener->id")
            ->assertForbidden();
    }

    public function test_delete_event_listeners_success()
    {
        $eventListener = EventListener::factory()->event(MySimpleEvent::class)->create();

        $this->assertEquals(1, EventListener::count());

        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();
        $this->actingAs($user)->delete("custom/event-listeners/$eventListener->id")
            ->assertNoContent();

        $this->assertEquals(0, EventListener::count());
    }

    public function test_delete_event_listeners_forbidden()
    {
        $eventListener = EventListener::factory()->event(MySimpleEvent::class)->create();

        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user)->delete("custom/event-listeners/$eventListener->id")
            ->assertForbidden();

        $this->assertEquals(1, EventListener::count());
    }

    public function test_get_invalid_event_class()
    {
        $eventListener = EventListener::factory(['event' => 'foo'])->create();

        $this->expectExceptionMessage('Invalid event foo');
        $eventListener->getEventClass();
    }
}
