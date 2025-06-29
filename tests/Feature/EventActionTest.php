<?php

namespace Tests\Feature;

use App\Actions\ComplexEventAction;
use App\Actions\SimpleEventAction;
use App\Events\MySimpleEvent;
use App\Models\User;
use Comhon\CustomAction\Models\DefaultSetting;
use Comhon\CustomAction\Models\EventAction;
use Comhon\CustomAction\Models\EventListener;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\SetUpWithModelRegistrationTrait;
use Tests\TestCase;

class EventActionTest extends TestCase
{
    use RefreshDatabase;
    use SetUpWithModelRegistrationTrait;

    #[DataProvider('providerBoolean')]
    public function test_list_event_listener_actions($withFilter)
    {
        $eventAction1 = EventAction::factory(['name' => 'my event action one'])
            ->action(SimpleEventAction::class)
            ->create();
        $eventAction2 = EventAction::factory(['name' => 'my event action two'])
            ->action(ComplexEventAction::class, $eventAction1->eventListener)
            ->create();

        $data = [
            [
                'id' => $eventAction1->id,
                'type' => 'simple-event-action',
                'name' => 'my event action one',
            ],
            [
                'id' => $eventAction2->id,
                'type' => 'complex-event-action',
                'name' => 'my event action two',
            ],
        ];
        if ($withFilter) {
            array_shift($data);
        }

        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();
        $params = $withFilter ? http_build_query(['name' => 'two']) : '';
        $this->actingAs($user)
            ->getJson("custom/event-listeners/{$eventAction1->eventListener->id}/actions?$params")
            ->assertJsonCount(count($data), 'data')
            ->assertJson(['data' => $data])
            ->assertJsonMissingPath('data.0.default_setting');
    }

    public function test_list_event_listener_actions_forbidden()
    {
        $eventListener = EventListener::factory()->create();

        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user)->getJson("custom/event-listeners/$eventListener->id/actions")
            ->assertForbidden();
    }

    public function test_store_event_listener_action_success()
    {
        $event = $this->getUniqueName(MySimpleEvent::class);
        $eventListener = EventListener::factory(['event' => $event])->create();
        $this->assertEquals(0, $eventListener->eventActions()->count());

        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();
        $inputs = [
            'name' => 'my event action',
            'type' => $this->getUniqueName(SimpleEventAction::class),
            'settings' => ['text' => 'foo'],
        ];
        $response = $this->actingAs($user)
            ->postJson("custom/event-listeners/$eventListener->id/actions", $inputs)
            ->assertCreated()
            ->assertJsonStructure(['data' => ['id']]);

        $eventAction = EventAction::findOrFail($response->json('data.id'));
        $response->assertJson([
            'data' => [
                'id' => $eventAction->id,
                'name' => $inputs['name'],
                'type' => $inputs['type'],
                'default_setting' => [
                    'id' => $eventAction->defaultSetting->id,
                    'settings' => $inputs['settings'],
                ],
            ],
        ]);

        $this->assertEquals(1, EventAction::count());
        $this->assertEquals(1, DefaultSetting::count());
        $this->assertEquals(1, $eventListener->eventActions()->count());
        $this->assertEquals($inputs['type'], $eventAction->type);
        $this->assertEquals($inputs['settings'], $eventAction->defaultSetting->settings);
    }

    public function test_store_event_listener_action_invalid_action()
    {
        $eventListener = EventListener::factory(['event' => 'bad-event'])->create();

        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();
        $actionValues = [
            'type' => 'bad-action',
            'settings' => [],
        ];
        $this->actingAs($user)->postJson("custom/event-listeners/$eventListener->id/actions", $actionValues)
            ->assertUnprocessable()
            ->assertJson([
                'message' => 'Action bad-action not found. (and 1 more error)',
                'errors' => [
                    'type' => [
                        'Action bad-action not found.',
                    ],
                    'name' => [
                        'The name field is required.',
                    ],
                ],
            ]);
    }

    public function test_store_event_listener_action_forbidden()
    {
        $eventListener = EventListener::factory()->create();

        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user)->postJson("custom/event-listeners/$eventListener->id/actions")
            ->assertForbidden();
    }

    public function test_get_event_listener_action()
    {
        $action = EventAction::factory()->action(SimpleEventAction::class)->withDefaultSettings()->create();

        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();
        $this->actingAs($user)->getJson("custom/event-actions/$action->id")
            ->assertOk()
            ->assertJson([
                'data' => [
                    'id' => $action->id,
                    'name' => $action->name,
                    'event_listener_id' => $action->event_listener_id,
                    'default_setting' => [
                        'id' => $action->defaultSetting->id,
                    ],
                ],
            ]);
    }

    public function test_get_event_listener_action_forbidden()
    {
        $action = EventAction::factory()->action(SimpleEventAction::class)->create();

        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user)->getJson("custom/event-actions/$action->id")
            ->assertForbidden();
    }

    public function test_update_event_listener_action()
    {
        $action = EventAction::factory()->action(SimpleEventAction::class)->create();
        $updateName = 'updated event action';

        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();
        $this->actingAs($user)->putJson("custom/event-actions/$action->id", ['name' => $updateName])
            ->assertOk()
            ->assertJson([
                'data' => [
                    'name' => $updateName,
                ],
            ]);
        $this->assertEquals($updateName, $action->refresh()->name);
    }

    public function test_update_event_listener_action_forbidden()
    {
        $action = EventAction::factory()->action(SimpleEventAction::class)->create();

        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user)->putJson("custom/event-actions/$action->id")->assertForbidden();
    }

    public function test_delete_event_action()
    {
        $action = EventAction::factory()->action(SimpleEventAction::class)->withDefaultSettings()->create();
        $eventListener = $action->eventListener;

        $this->assertEquals(1, $eventListener->eventActions()->count());

        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();
        $this->actingAs($user)->deleteJson("custom/event-actions/$action->id")
            ->assertNoContent();

        $this->assertEquals(0, $eventListener->eventActions()->count());
        $this->assertEquals(0, EventAction::count());
    }

    public function test_delete_event_action_forbidden()
    {
        $action = EventAction::factory()->action(SimpleEventAction::class)->create();

        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user)->deleteJson("custom/event-actions/$action->id")
            ->assertForbidden();

        $this->assertEquals(1, $action->eventListener->eventActions()->count());
    }

    public function test_simulate_event_action_success()
    {
        $action = EventAction::factory()->action(ComplexEventAction::class)->create();

        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();

        $inputs = [
            'settings' => [
                'text' => 'simple text',
            ],
            'localized_settings' => [
                'localized_text' => 'localized text',
            ],
        ];

        $userCount = User::count();

        Event::fake();
        $this->actingAs($user)->postJson("custom/event-actions/$action->id/simulate", $inputs)
            ->assertOk()
            ->assertJson([
                'data' => [
                    'success' => true,
                    'result' => [
                        'action' => 'complex-event-action',
                        'output' => [
                            'text' => 'simple text',
                            'user_status' => 'foo',
                        ],
                    ],
                ],
            ])->assertJsonMissingPath('data.state');

        // a User is created during context faking
        // But no data must be permenently saved (any database changes must be rollbacked)
        Event::assertDispatched('eloquent.created: '.User::class);
        $this->assertEquals($userCount, User::count());
    }

    public function test_simulate_event_action_with_state_success()
    {
        $action = EventAction::factory()->action(ComplexEventAction::class)->create();

        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();

        $inputs = [
            'settings' => [
                'text' => 'simple text',
            ],
            'localized_settings' => [
                'localized_text' => 'localized text',
            ],
            'states' => [
                'status_1',
                ['status' => 10],
                [
                    'status_2',
                    ['status' => 20],
                    ['status_3', ['status' => 30]],
                ],
            ],
        ];

        $this->actingAs($user)->postJson("custom/event-actions/$action->id/simulate", $inputs)
            ->assertOk()
            ->assertJson([
                'data' => [
                    [
                        'success' => true,
                        'result' => [
                            'action' => 'complex-event-action',
                            'output' => [
                                'text' => 'simple text',
                                'user_status' => '-status_1',
                            ],
                        ],
                        'state' => ['status_1'],
                    ],
                    [
                        'success' => true,
                        'result' => [
                            'action' => 'complex-event-action',
                            'output' => [
                                'text' => 'simple text',
                                'user_status' => '-status_10',
                            ],
                        ],
                        'state' => [['status' => 10]],
                    ],
                    [
                        'success' => true,
                        'result' => [
                            'action' => 'complex-event-action',
                            'output' => [
                                'text' => 'simple text',
                                'user_status' => '-status_2-status_20-status_3',
                            ],
                        ],
                        'state' => ['status_2', ['status' => 20], 'status_3'],
                    ],
                    [
                        'success' => true,
                        'result' => [
                            'action' => 'complex-event-action',
                            'output' => [
                                'text' => 'simple text',
                                'user_status' => '-status_2-status_20-status_30',
                            ],
                        ],
                        'state' => ['status_2', ['status' => 20], ['status' => 30]],
                    ],
                ],
            ]);
    }

    #[DataProvider('providerBoolean')]
    public function test_simulate_event_action_with_state_error($debug)
    {
        config(['app.debug' => $debug]);
        $action = EventAction::factory()->action(ComplexEventAction::class)->create();

        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();

        $inputs = [
            'settings' => [
                'text' => 'simple text',
            ],
            'localized_settings' => [
                'localized_text' => 'localized text',
            ],
            'states' => [
                ['status' => 1000],
            ],
        ];

        $json = $this->actingAs($user)->postJson("custom/event-actions/$action->id/simulate", $inputs)
            ->assertOk()
            ->assertJson([
                'data' => [
                    [
                        'success' => false,
                        'message' => 'message',
                        'state' => [['status' => 1000]],
                    ],
                ],
            ])->json('data.0');

        if ($debug) {
            $this->assertArrayHasKey('trace', $json);
            $this->assertIsArray($json['trace']);
        } else {
            $this->assertArrayNotHasKey('trace', $json);
        }
    }

    public function test_simulate_event_action_not_simulatable()
    {
        $action = EventAction::factory()->action(ComplexEventAction::class)->create();
        $action->eventListener
            ->forceFill(['event' => $this->getUniqueName(MySimpleEvent::class)])
            ->save();

        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();

        $inputs = [];

        $this->actingAs($user)->postJson("custom/event-actions/$action->id/simulate", $inputs)
            ->assertUnprocessable()
            ->assertJson([
                'message' => 'cannot simulate action, event my-simple-event is not fakable',
            ]);
    }

    public function test_simulate_event_action_forbidden()
    {
        $action = EventAction::factory()->action(ComplexEventAction::class)->create();

        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user)->postJson("custom/event-actions/$action->id/simulate")
            ->assertForbidden();
    }
}
