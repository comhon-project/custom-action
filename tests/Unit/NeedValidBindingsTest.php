<?php

namespace Tests\Unit;

use App\Actions\TestActionCache;
use Comhon\CustomAction\Models\ManualAction;
use Tests\TestCase;

class NeedValidBindingsTest extends TestCase
{
    public function test_get_validated_bindings_without_cache()
    {
        $action = new TestActionCache(ManualAction::factory()->create());
        $res = $action->getAllValidatedBindings('en');
        $this->assertEquals(1, $res['index']);

        $res = $action->getAllValidatedBindings('en');
        $this->assertEquals(2, $res['index']);
    }

    public function test_get_validated_bindings_with_cache()
    {
        $action = new TestActionCache(ManualAction::factory()->create());
        $res = $action->getAllValidatedBindings('en', true);
        $this->assertEquals(1, $res['index']);

        $res = $action->getAllValidatedBindings('en', true);
        $this->assertEquals(1, $res['index']);
    }
}
