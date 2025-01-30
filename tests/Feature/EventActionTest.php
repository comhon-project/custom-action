<?php

namespace Tests\Feature;

use App\Models\User;
use Comhon\CustomAction\Models\DefaultSetting;
use Comhon\CustomAction\Models\EventAction;
use Comhon\CustomAction\Models\EventListener;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\SetUpWithModelRegistrationTrait;
use Tests\TestCase;

class EventActionTest extends TestCase
{
    use RefreshDatabase;
    use SetUpWithModelRegistrationTrait;

    public function test_list_event_listener_actions()
    {
        $toUser = User::factory()->create();

        // create event listener for CompanyRegistered event
        $eventListener = EventListener::factory()->genericRegistrationCompany([$toUser->id])->create();

        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();
        $response = $this->actingAs($user)->getJson(
            "custom/event-listeners/$eventListener->id/actions"
        );

        $actions = EventAction::all(['id']);
        $response->assertJson([
            'data' => [
                [
                    'id' => $actions[0]->id,
                    'type' => 'send-email',
                    'default_setting' => [
                        'id' => $actions[0]->defaultSetting->id,
                    ],
                ],
                [
                    'id' => $actions[1]->id,
                    'type' => 'send-email',
                    'default_setting' => [
                        'id' => $actions[1]->defaultSetting->id,
                    ],
                ],
            ],
        ]);
    }

    public function test_list_event_listener_actions_with_filter()
    {
        // create event listener for CompanyRegistered event
        $eventListener = EventListener::factory([
            'event' => 'company-registered',
        ])->create();
        $eventAction = EventAction::factory(['name' => 'my one'])
            ->sendMailRegistrationCompany()
            ->for($eventListener, 'eventListener')->create();
        EventAction::factory(['name' => 'my two'])
            ->sendMailRegistrationCompany()
            ->for($eventListener, 'eventListener')->create();

        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();
        $params = http_build_query(['name' => 'one']);
        $this->actingAs($user)->getJson("custom/event-listeners/$eventListener->id/actions?$params")
            ->assertJsonCount(1, 'data')->assertJson([
                'data' => [
                    [
                        'id' => $eventAction->id,
                        'type' => 'send-email',
                        'name' => 'my one',
                    ],
                ],
            ]);
    }

    public function test_list_event_listener_actions_forbidden()
    {
        $toUser = User::factory()->create();

        // create event listener for CompanyRegistered event
        $eventListener = EventListener::factory()->genericRegistrationCompany([$toUser->id])->create();

        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user)->getJson("custom/event-listeners/$eventListener->id/actions")
            ->assertForbidden();
    }

    public function test_store_event_listener_action_success()
    {
        // create event listener for CompanyRegistered event
        $eventListener = EventListener::factory()->create();
        $this->assertEquals(0, $eventListener->eventActions()->count());

        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();
        $actionValues = [
            'name' => 'my custom event listener',
            'type' => 'send-email',
            'settings' => [
                'recipients' => ['to' => [
                    'static' => ['mailables' => [
                        ['recipient_type' => 'user', 'recipient_id' => User::factory()->create()->id],
                    ]],
                    'bindings' => ['mailables' => ['user']],
                ]],
                'attachments' => ['logo'],
            ],
        ];
        $response = $this->actingAs($user)->postJson("custom/event-listeners/$eventListener->id/actions", $actionValues);
        $response->assertCreated();
        $this->assertEquals(1, EventAction::count());
        $this->assertEquals(1, DefaultSetting::count());
        $this->assertEquals(1, $eventListener->eventActions()->count());
        $eventAction = EventAction::findOrFail($response->json('data.id'));

        $response->assertJson([
            'data' => [
                'id' => $eventAction->id,
                'name' => $actionValues['name'],
                'type' => $actionValues['type'],
                'default_setting' => [
                    'id' => $eventAction->defaultSetting->id,
                    'settings' => $actionValues['settings'],
                ],
            ],
        ]);
        $this->assertEquals('send-email', $eventAction->type);
        $this->assertEquals($actionValues['settings'], $eventAction->defaultSetting->settings);
    }

    public function test_store_event_listener_bad_action_success()
    {
        // create event listener for CompanyRegistered event
        $eventListener = EventListener::factory([
            'event' => 'bad-event',
        ])->create();
        $this->assertEquals(0, $eventListener->eventActions()->count());

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
        // create event listener for CompanyRegistered event
        $eventListener = EventListener::factory()->create();
        $this->assertEquals(0, $eventListener->eventActions()->count());

        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user)->postJson("custom/event-listeners/$eventListener->id/actions")
            ->assertForbidden();
    }

    public function test_get_event_listener_action()
    {
        // create event listener for CompanyRegistered event
        $eventListener = EventListener::factory()->genericRegistrationCompany()->create();
        $action = $eventListener->eventActions[0];

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
        // create event listener for CompanyRegistered event
        $eventListener = EventListener::factory()->genericRegistrationCompany()->create();
        $action = $eventListener->eventActions[0];

        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user)->getJson("custom/event-actions/$action->id")
            ->assertForbidden();
    }

    public function test_update_event_listener_action()
    {
        // create event listener for CompanyRegistered event
        $eventListener = EventListener::factory()->genericRegistrationCompany()->create();

        $this->assertCount(1, $eventListener->eventActions);
        $action = $eventListener->eventActions[0];
        $updateName = 'updated event action';

        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();
        $this->actingAs($user)->putJson("custom/event-actions/$action->id", [
            'name' => $updateName,
        ])->assertOk()
            ->assertJson([
                'data' => [
                    'name' => $updateName,
                ],
            ]);
        $this->assertEquals($updateName, $action->refresh()->name);
    }

    public function test_update_event_listener_action_forbidden()
    {
        // create event listener for CompanyRegistered event
        $eventListener = EventListener::factory()->genericRegistrationCompany()->create();
        $action = $eventListener->eventActions[0];

        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user)->putJson("custom/event-actions/$action->id")->assertForbidden();
    }

    public function test_delete_event_action()
    {
        // create event listener for CompanyRegistered event
        $eventListener = EventListener::factory()->genericRegistrationCompany()->create();

        $this->assertCount(1, $eventListener->eventActions);
        $action = $eventListener->eventActions[0];

        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();
        $response = $this->actingAs($user)->deleteJson(
            "custom/event-actions/$action->id"
        );
        $response->assertNoContent();
        $this->assertEquals(0, $eventListener->eventActions()->count());
        $this->assertEquals(0, EventAction::count());
    }

    public function test_delete_event_action_forbidden()
    {
        // create event listener for CompanyRegistered event
        $eventListener = EventListener::factory()->genericRegistrationCompany()->create();

        $action = $eventListener->eventActions[0];

        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user)->deleteJson(
            "custom/event-actions/$action->id"
        )->assertForbidden();

        $this->assertEquals(1, $eventListener->eventActions()->count());
    }
}
