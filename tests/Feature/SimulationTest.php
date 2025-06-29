<?php

namespace Tests\Feature;

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
}
