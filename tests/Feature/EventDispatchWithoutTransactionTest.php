<?php

namespace Tests\Feature;

use App\Actions\FailingEventAction;
use App\Actions\SimpleEventAction;
use App\Events\MySimpleEvent;
use App\Models\Output;
use App\Models\User;
use Comhon\CustomAction\Events\EventActionError;
use Comhon\CustomAction\Models\EventAction;
use Illuminate\Database\DeadlockException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Tests\SetUpWithModelRegistrationTrait;
use Tests\TestCase;

/**
 * Without RefreshDatabase, that wraps each test in a transaction.
 */
class EventDispatchWithoutTransactionTest extends TestCase
{
    use SetUpWithModelRegistrationTrait;

    public function test_failing_action_is_not_rolled_back_outside_transaction()
    {
        $user = User::factory()->create();
        EventAction::factory()->action(FailingEventAction::class)->withDefaultSettings()->create();

        Event::fake([EventActionError::class]);

        $this->assertEquals(0, DB::transactionLevel());
        MySimpleEvent::dispatch($user);

        // no transaction is started by the dispatcher, the output written by the failing action is kept
        $this->assertEquals(['failing-event-action'], Output::pluck('action')->all());
        Event::assertDispatched(EventActionError::class, 1);
    }

    public function test_deadlock_is_not_thrown_back_outside_transaction()
    {
        $user = User::factory()->create();
        EventAction::factory()->action(SimpleEventAction::class)->withDefaultSettings()->create();

        // the action fails with a deadlock when it writes in database
        Output::creating(fn () => throw new DeadlockException('Deadlock found when trying to get lock'));
        Event::fake([EventActionError::class]);

        MySimpleEvent::dispatch($user);

        // there is no transaction of the caller to fail, the error is handled as any other error
        Event::assertDispatched(EventActionError::class, fn (EventActionError $error) => $error->th instanceof DeadlockException);
    }
}
