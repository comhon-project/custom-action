<?php

namespace Tests;

use App\Actions\BadAction;
use App\Actions\ComplexEventAction;
use App\Actions\ComplexManualAction;
use App\Actions\FakableNotSimulatabeAction;
use App\Actions\QueuedEventAction;
use App\Actions\QueuedManualAction;
use App\Actions\SendAttachedEmail;
use App\Actions\SendManualSimpleEmail;
use App\Actions\SimpleEventAction;
use App\Actions\SimpleManualAction;
use App\Actions\SimulatabeNotFakableAction;
use App\Events\BadEvent;
use App\Events\MyComplexEvent;
use App\Events\MyEmailEvent;
use App\Events\MySimpleEvent;
use App\Models\User;
use Comhon\CustomAction\Actions\Email\QueueAutomaticEmail;
use Comhon\CustomAction\Actions\Email\SendAutomaticEmail;
use Comhon\CustomAction\Facades\CustomActionModelResolver;

trait SetUpWithModelRegistrationTrait
{
    public function setUp(): void
    {
        parent::setUp();

        CustomActionModelResolver::register([
            'user' => User::class,

            'send-automatic-email' => SendAutomaticEmail::class,
            'queue-automatic-email' => QueueAutomaticEmail::class,
            'send-attached-email' => SendAttachedEmail::class,
            'send-manual-simple-email' => SendManualSimpleEmail::class,
            'my-email-event' => MyEmailEvent::class,

            'simple-manual-action' => SimpleManualAction::class,
            'queued-manual-action' => QueuedManualAction::class,
            'complex-manual-action' => ComplexManualAction::class,
            'simple-event-action' => SimpleEventAction::class,
            'queued-event-action' => QueuedEventAction::class,
            'complex-event-action' => ComplexEventAction::class,

            'fakable-not-simulatable-action' => FakableNotSimulatabeAction::class,
            'simulatable-not-fakable-action' => SimulatabeNotFakableAction::class,
            'bad-event' => BadEvent::class,
            'bad-action' => BadAction::class,

            'my-simple-event' => MySimpleEvent::class,
            'my-complex-event' => MyComplexEvent::class,
        ]);
    }
}
