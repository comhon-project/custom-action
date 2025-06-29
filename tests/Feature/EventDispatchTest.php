<?php

namespace Tests\Feature;

use App\Actions\ComplexEventAction;
use App\Actions\QueuedEventAction;
use App\Actions\SimpleEventAction;
use App\Events\MyComplexEvent;
use App\Events\MySimpleEvent;
use App\Models\Output;
use App\Models\User;
use Comhon\CustomAction\Events\EventActionError;
use Comhon\CustomAction\Listeners\EventActionDispatcher;
use Comhon\CustomAction\Listeners\QueuedEventActionDispatcher;
use Comhon\CustomAction\Models\DefaultSetting;
use Comhon\CustomAction\Models\EventAction;
use Comhon\CustomAction\Models\EventListener;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\SetUpWithModelRegistrationTrait;
use Tests\TestCase;

class EventDispatchTest extends TestCase
{
    use RefreshDatabase;
    use SetUpWithModelRegistrationTrait;

    private function dispatcher(): EventActionDispatcher
    {
        return app(EventActionDispatcher::class);
    }

    private function queuedDispatcher(): QueuedEventActionDispatcher
    {
        return app(QueuedEventActionDispatcher::class);
    }

    public function test_via_connection_with_default_queue_sync()
    {
        $this->assertEquals('sync', $this->queuedDispatcher()->viaConnection());
    }

    public function test_via_connection_with_defined_config()
    {
        config(['custom-action.event_action_dispatcher.queue_connection' => 'foo']);
        $this->assertEquals('foo', $this->queuedDispatcher()->viaConnection());
    }

    public function test_via_queue_with_default_queue_sync()
    {
        $this->assertNull($this->queuedDispatcher()->viaQueue());
    }

    public function test_via_queue_with_default_queue_database()
    {
        config(['queue.default' => 'database']);
        $this->assertEquals('default', $this->queuedDispatcher()->viaQueue());
    }

    public function test_via_queue_with_defined_config()
    {
        config(['custom-action.event_action_dispatcher.queue_name' => 'bar']);
        $this->assertEquals('bar', $this->queuedDispatcher()->viaQueue());
    }

    #[DataProvider('providerBoolean')]
    public function test_event_listener_success($addScope)
    {
        $user = User::factory('name')->create();
        $scope = $addScope ? ['scope' => ['user.id' => $user->id]] : null;

        $eventListener = EventListener::factory($scope)
            ->event(MyComplexEvent::class)
            ->create();
        $eventAction = EventAction::factory()
            ->action(ComplexEventAction::class, $eventListener)
            ->withDefaultSettings()
            ->create();

        if ($addScope) {
            // add event listener that have a scope that doesn't match
            $eventListener = EventListener::factory(['scope' => ['user.id' => $user->id + 1]])
                ->event(MyComplexEvent::class)
                ->create();
            EventAction::factory()
                ->action(ComplexEventAction::class, $eventListener)
                ->withDefaultSettings()
                ->create();
        }

        // other listener that listen for another event
        // must never been taken in account
        EventAction::factory()
            ->action(SimpleEventAction::class)
            ->withDefaultSettings()
            ->create();

        $this->assertEquals(0, Output::count());

        Queue::fake();

        MyComplexEvent::dispatch($user);

        Queue::assertNothingPushed();

        $this->assertEquals(1, Output::count());
        $this->assertArraySubset(
            [
                'action' => 'complex-event-action',
                'setting_id' => $eventAction->defaultSetting->id,
                'setting_class' => DefaultSetting::class,
                'localized_setting_id' => $eventAction->defaultSetting->localizedSettings->first()->id,
                'output' => [
                    'user_id' => $user->id,
                    'user_status' => 'foo',
                ],
            ],
            Output::firstOrFail()->toArray()
        );
    }

    public function test_event_listener_several_matches()
    {
        $user = User::factory('name')->create();

        $eventListener = EventListener::factory(['scope' => ['user.id' => $user->id]])
            ->event(MyComplexEvent::class)
            ->create();
        EventAction::factory(2)
            ->action(ComplexEventAction::class, $eventListener)
            ->withDefaultSettings()
            ->create();

        EventAction::factory()
            ->action(ComplexEventAction::class)
            ->withDefaultSettings()
            ->create();

        MyComplexEvent::dispatch($user);

        $this->assertEquals(3, Output::count());
    }

    public function test_event_listener_queued_actions()
    {
        $user = User::factory('name')->create();

        EventAction::factory(2)
            ->action(QueuedEventAction::class)
            ->withDefaultSettings()
            ->create();

        Queue::fake();

        MySimpleEvent::dispatch($user);

        Queue::assertPushed(QueuedEventAction::class, 2);
    }

    #[DataProvider('providerBadActionTypes')]
    public function test_handle_event_with_not_existing_action($actionType)
    {
        $eventAction = EventAction::factory()
            ->action(SimpleEventAction::class)
            ->create();

        $eventAction->type = $actionType;
        $eventAction->save();

        Event::fake();

        // event are faked to catch EventActionError
        // so we can't dispatch MySimpleEvent
        // instead we call directly the handle method of action event dispatcher
        $event = new MySimpleEvent(User::factory()->create());
        $this->dispatcher()->handle($event);

        Event::assertDispatched(EventActionError::class, 1);

        Event::assertDispatched(function (EventActionError $eventActionError) use ($actionType) {
            $this->assertStringContainsString(
                "Invalid action type $actionType on model Comhon\CustomAction\Models\EventAction",
                $eventActionError->th->getMessage(),
            );

            return true;
        });
    }

    public static function providerBadActionTypes()
    {
        return [
            ['foo'], // doesn't exists
            ['user'], // doesn't implements of CustomActionInterface
            ['simple-manual-action'], // doesn't implements of CallableFromEventInterface
        ];
    }

    public function test_should_not_queue_dispatcher()
    {
        Queue::fake();

        MySimpleEvent::dispatch();

        Queue::assertNothingPushed();
    }

    public function test_should_queue_dispatcher()
    {
        // there is a specific case in defineEnvironment() for this test function
        // to set the config "custom-action.event_action_dispatcher.should_queue" to true

        Queue::fake();

        MySimpleEvent::dispatch();

        Queue::assertPushed(CallQueuedListener::class, 1);
        Queue::assertPushed(function (CallQueuedListener $dispatcher) {
            return $dispatcher->class === QueuedEventActionDispatcher::class;
        });
    }
}
