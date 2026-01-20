<?php

namespace Tests\Feature;

use App\Actions\ComplexManualAction;
use Comhon\CustomAction\Exceptions\NotInSafeFakeException;
use Comhon\CustomAction\Models\ManualAction;
use Comhon\CustomAction\Services\ActionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Tests\SetUpWithModelRegistrationTrait;
use Tests\TestCase;

class SimulationTest extends TestCase
{
    use RefreshDatabase;
    use SetUpWithModelRegistrationTrait;

    public function test_is_faking_safe()
    {
        $this->assertFalse(ActionService::isFakingSafe());

        $action = ManualAction::factory(['type' => 'simulating-context-test-action'])->create();

        $result = app(ActionService::class)->simulate($action, []);

        $this->assertTrue($result['success']);
        $this->assertTrue($result['result']['was_faking_safe']);
        $this->assertFalse(ActionService::isFakingSafe());
    }

    public function test_simulate_function_doesnt_exists()
    {
        $action = ManualAction::factory(['type' => 'bad-action'])->create();

        $this->expectExceptionMessage("simulate method doesn't exist on class App\Actions\BadAction");
        app(ActionService::class)->simulate($action, []);
    }

    public function test_doesnt_have_fake_state()
    {
        $action = ManualAction::factory(['type' => 'bad-action'])->create();

        $this->expectException(UnprocessableEntityHttpException::class);
        $this->expectExceptionMessage('bad-action has no state to simulate action');
        app(ActionService::class)->simulate($action, ['states' => ['foo']]);
    }

    public function test_ensure_faking_safe_throws_exception()
    {
        $this->expectException(NotInSafeFakeException::class);
        $this->expectExceptionMessage('Not in safe fake context');
        ComplexManualAction::fake();
    }
}
