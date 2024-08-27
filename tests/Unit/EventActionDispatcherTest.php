<?php

namespace Tests\Unit;

use App\Events\CompanyRegistered;
use App\Events\MyEventWithoutBindings;
use App\Models\Company;
use App\Models\User;
use Comhon\CustomAction\Listeners\EventActionDispatcher;
use Comhon\CustomAction\Listeners\QueuedEventActionDispatcher;
use Comhon\CustomAction\Models\EventListener;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Support\Facades\Queue;
use Tests\SetUpWithModelRegistration;
use Tests\TestCase;

class EventActionDispatcherTest extends TestCase
{
    use SetUpWithModelRegistration;

    public function dispatcher(): EventActionDispatcher
    {
        return app(EventActionDispatcher::class);
    }

    public function queuedDispatcher(): QueuedEventActionDispatcher
    {
        return app(QueuedEventActionDispatcher::class);
    }

    public function testViaConnectionWithDefaultQueueSync()
    {
        $this->assertEquals('sync', $this->queuedDispatcher()->viaConnection());
    }

    public function testViaConnectionWithDefinedConfig()
    {
        config(['custom-action.event_action_dispatcher.queue_connection' => 'foo']);
        $this->assertEquals('foo', $this->queuedDispatcher()->viaConnection());
    }

    public function testViaQueueWithDefaultQueueSync()
    {
        $this->assertNull($this->queuedDispatcher()->viaQueue());
    }

    public function testViaQueueWithDefaultQueueDatabase()
    {
        config(['queue.default' => 'database']);
        $this->assertEquals('default', $this->queuedDispatcher()->viaQueue());
    }

    public function testViaQueueWithDefinedConfig()
    {
        config(['custom-action.event_action_dispatcher.queue_name' => 'bar']);
        $this->assertEquals('bar', $this->queuedDispatcher()->viaQueue());
    }

    public function testHandleEventWithNotExistingAction()
    {
        $listener = EventListener::factory()->genericRegistrationCompany()->create();
        $event = new CompanyRegistered(Company::factory()->create(), User::factory()->create());
        foreach ($listener->eventActions as $action) {
            $action->type = 'foo';
            $action->save();
        }

        $this->expectExceptionMessage('Invalid action type foo');
        $this->dispatcher()->handle($event);
    }

    public function testHandleEventWithActionWrongClass()
    {
        $listener = EventListener::factory()->genericRegistrationCompany()->create();
        $event = new CompanyRegistered(Company::factory()->create(), User::factory()->create());
        foreach ($listener->eventActions as $action) {
            $action->type = 'company';
            $action->save();
        }

        $this->expectExceptionMessage('invalid action company, must be an action instance of CustomActionInterface');
        $this->dispatcher()->handle($event);
    }

    public function testShouldNotQueueDispatcher()
    {
        Queue::fake();

        MyEventWithoutBindings::dispatch();

        Queue::assertNothingPushed();
    }

    public function testShouldQueueDispatcher()
    {
        // there is a specific case in defineEnvironment() for this test function
        // to set the config "custom-action.event_action_dispatcher.should_queue" to true

        Queue::fake();

        MyEventWithoutBindings::dispatch();

        Queue::assertPushed(CallQueuedListener::class, 1);
        Queue::assertPushed(function (CallQueuedListener $dispatcher) {
            return $dispatcher->class === QueuedEventActionDispatcher::class;
        });
    }
}
