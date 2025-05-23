<?php

namespace Tests\Unit;

use App\Events\CompanyRegistered;
use App\Events\MyEventWithoutContext;
use App\Models\Company;
use App\Models\User;
use Comhon\CustomAction\Events\EventActionError;
use Comhon\CustomAction\Listeners\EventActionDispatcher;
use Comhon\CustomAction\Listeners\QueuedEventActionDispatcher;
use Comhon\CustomAction\Models\EventListener;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\SetUpWithModelRegistrationTrait;
use Tests\TestCase;

class EventActionDispatcherTest extends TestCase
{
    use RefreshDatabase;
    use SetUpWithModelRegistrationTrait;

    public function dispatcher(): EventActionDispatcher
    {
        return app(EventActionDispatcher::class);
    }

    public function queuedDispatcher(): QueuedEventActionDispatcher
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

    #[DataProvider('providerBadActionTypes')]
    public function test_handle_event_with_not_existing_action($actionType)
    {
        $listener = EventListener::factory()->genericRegistrationCompany()->create();
        $event = new CompanyRegistered(Company::factory()->create(), User::factory()->create());
        foreach ($listener->eventActions as $action) {
            $action->type = $actionType;
            $action->save();
        }

        Event::fake();

        $this->dispatcher()->handle($event);

        Event::assertDispatched(EventActionError::class, 1);

        Event::assertDispatched(function (EventActionError $event) use ($actionType) {
            $this->assertStringContainsString(
                "Invalid action type $actionType on model Comhon\CustomAction\Models\EventAction",
                $event->th->getMessage(),
            );

            return true;
        });
    }

    public static function providerBadActionTypes()
    {
        return [
            ['foo'], // doesn't exists
            ['company'], // doesn't implements of CustomActionInterface
            ['send-manual-company-email'], // doesn't implements of CallableFromEventInterface
        ];
    }

    public function test_should_not_queue_dispatcher()
    {
        Queue::fake();

        MyEventWithoutContext::dispatch();

        Queue::assertNothingPushed();
    }

    public function test_should_queue_dispatcher()
    {
        // there is a specific case in defineEnvironment() for this test function
        // to set the config "custom-action.event_action_dispatcher.should_queue" to true

        Queue::fake();

        MyEventWithoutContext::dispatch();

        Queue::assertPushed(CallQueuedListener::class, 1);
        Queue::assertPushed(function (CallQueuedListener $dispatcher) {
            return $dispatcher->class === QueuedEventActionDispatcher::class;
        });
    }
}
