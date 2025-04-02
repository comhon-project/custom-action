<?php

namespace Tests\Unit;

use App\Actions\TestActionCache;
use Comhon\CustomAction\Models\ManualAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NeedValidContextTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_validated_context_without_cache()
    {
        ManualAction::factory()->create();
        $action = new TestActionCache;
        $res = $action->getAllValidatedContext('en', false);
        $this->assertEquals(1, $res['index']);

        $res = $action->getAllValidatedContext('en', false);
        $this->assertEquals(2, $res['index']);
    }

    public function test_get_validated_context_with_cache()
    {
        ManualAction::factory()->create();
        $action = new TestActionCache;
        $res = $action->getAllValidatedContext('en', true);
        $this->assertEquals(1, $res['index']);

        $res = $action->getAllValidatedContext('en', true);
        $this->assertEquals(1, $res['index']);
    }
}
