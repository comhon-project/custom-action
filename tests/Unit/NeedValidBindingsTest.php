<?php

namespace Tests\Unit;

use App\Actions\TestActionCache;
use Comhon\CustomAction\Models\ManualAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NeedValidBindingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_validated_bindings_without_cache()
    {
        ManualAction::factory()->create();
        $action = new TestActionCache;
        $res = $action->getAllValidatedBindings('en', false);
        $this->assertEquals(1, $res['index']);

        $res = $action->getAllValidatedBindings('en', false);
        $this->assertEquals(2, $res['index']);
    }

    public function test_get_validated_bindings_with_cache()
    {
        ManualAction::factory()->create();
        $action = new TestActionCache;
        $res = $action->getAllValidatedBindings('en', true);
        $this->assertEquals(1, $res['index']);

        $res = $action->getAllValidatedBindings('en', true);
        $this->assertEquals(1, $res['index']);
    }
}
