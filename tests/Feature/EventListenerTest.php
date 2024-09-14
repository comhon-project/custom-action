<?php

namespace Tests\Feature;

use App\Models\User;
use Comhon\CustomAction\Models\ActionLocalizedSettings;
use Comhon\CustomAction\Models\ActionSettings;
use Comhon\CustomAction\Models\EventListener;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\SetUpWithModelRegistrationTrait;
use Tests\TestCase;

class EventListenerTest extends TestCase
{
    use RefreshDatabase;
    use SetUpWithModelRegistrationTrait;

    public function testGetEventListeners()
    {
        // create event listener for CompanyRegistered event
        $eventListener = EventListener::factory()->genericRegistrationCompany()->create();
        $eventListener2 = EventListener::factory()->genericRegistrationCompany(null, 'my company')->create();

        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();
        $this->actingAs($user)->getJson('custom/events/company-registered/listeners')
            ->assertJson([
                'data' => [
                    [
                        'id' => $eventListener->id,
                        'event' => 'company-registered',
                        'name' => 'My Custom Event Listener',
                        'scope' => null,
                    ],
                    [
                        'id' => $eventListener2->id,
                        'event' => 'company-registered',
                        'name' => 'My Custom Event Listener',
                        'scope' => [
                            'company' => [
                                'name' => 'my company',
                            ],
                        ],
                    ],
                ],
            ]);
    }

    public function testGetEventListenersWithFilter()
    {
        // create event listener for CompanyRegistered event
        $eventListener = EventListener::factory(['name' => 'my one'])->genericRegistrationCompany()->create();
        EventListener::factory(['name' => 'my two'])->genericRegistrationCompany()->create();

        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();
        $params = http_build_query(['name' => 'one']);
        $this->actingAs($user)->getJson("custom/events/company-registered/listeners?$params")
            ->assertJsonCount(1, 'data')
            ->assertJson([
                'data' => [
                    [
                        'id' => $eventListener->id,
                        'event' => 'company-registered',
                        'name' => 'my one',
                        'scope' => null,
                    ],
                ],
            ]);
    }

    public function testGetEventListenersWithNotFoundEvent()
    {
        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();
        $response = $this->actingAs($user)->getJson('custom/events/unknown-event/listeners');

        $response->assertNotFound();
    }

    public function testGetEventListenersForbidden()
    {
        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user)->getJson('custom/events/company-registered/listeners')
            ->assertForbidden();
    }

    public function testStoreEventListeners()
    {
        $scope = [
            'company' => [
                'address' => 'nowhere',
            ],
        ];
        $this->assertEquals(0, EventListener::count());

        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();
        $response = $this->actingAs($user)->postJson('custom/events/company-registered/listeners', [
            'scope' => $scope,
            'name' => 'my event listener',
        ]);
        $response->assertCreated();
        $this->assertEquals(1, EventListener::count());
        $eventListener = EventListener::all()->first();

        $response->assertJson([
            'data' => [
                'event' => 'company-registered',
                'scope' => $scope,
                'name' => 'my event listener',
                'id' => $eventListener->id,
            ],
        ]);
        $this->assertEquals('company-registered', $eventListener->event);
        $this->assertEquals($scope, $eventListener->scope);
    }

    public function testStoreEventListenersWithNotFoundEvent()
    {
        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();
        $response = $this->actingAs($user)->postJson('custom/events/unkown-event/listeners', []);
        $response->assertNotFound();
    }

    public function testStoreEventListenersForbidden()
    {
        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user)->postJson('custom/events/company-registered/listeners')
            ->assertForbidden();
    }

    public function testUpdateEventListener()
    {
        $eventListener = EventListener::factory()->genericRegistrationCompany()->create();
        $this->assertNull($eventListener->scope);

        $scope = [
            'company' => [
                'address' => 'nowhere',
            ],
        ];
        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();
        $response = $this->actingAs($user)->putJson("custom/event-listeners/$eventListener->id", [
            'scope' => $scope,
            'name' => 'updated event listener',
        ]);
        $response->assertOk();
        $response->assertJson([
            'data' => [
                'id' => $eventListener->id,
                'event' => 'company-registered',
                'scope' => $scope,
                'name' => 'updated event listener',
            ],
        ]);
        $storedEventListener = EventListener::findOrFail($eventListener->id);
        $this->assertEquals($scope, $storedEventListener->scope);
    }

    public function testUpdateEventListenerForbidden()
    {
        $eventListener = EventListener::factory()->genericRegistrationCompany()->create();

        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user)->putJson("custom/event-listeners/$eventListener->id")
            ->assertForbidden();
    }

    public function testDeleteEventListeners()
    {
        $eventListener = EventListener::factory()->genericRegistrationCompany()->create();

        $this->assertEquals(1, EventListener::count());
        $this->assertEquals(1, ActionSettings::count());
        $this->assertEquals(4, ActionLocalizedSettings::count());

        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();
        $this->actingAs($user)->delete("custom/event-listeners/$eventListener->id")
            ->assertNoContent();

        $this->assertEquals(0, EventListener::count());
        $this->assertEquals(0, ActionSettings::count());
        $this->assertEquals(0, ActionLocalizedSettings::count());
    }

    public function testDeleteEventListenersForbidden()
    {
        $eventListener = EventListener::factory()->genericRegistrationCompany()->create();

        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user)->delete("custom/event-listeners/$eventListener->id")
            ->assertForbidden();

        $this->assertEquals(1, EventListener::count());
        $this->assertEquals(1, ActionSettings::count());
        $this->assertEquals(4, ActionLocalizedSettings::count());
    }
}
