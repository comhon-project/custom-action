<?php

namespace Tests\Unit;

use App\Actions\TestActionCache;
use Tests\TestCase;

class NeedValidContextTest extends TestCase
{
    public function test_get_validated_context_without_cache()
    {
        $action = new TestActionCache;
        $res = $action->getExposedValidatedContext(false, false);
        $this->assertEquals(1, $res['index']);

        $res = $action->getExposedValidatedContext(false, false);
        $this->assertEquals(2, $res['index']);
    }

    public function test_get_validated_context_with_cache()
    {
        $action = new TestActionCache;
        $res = $action->getExposedValidatedContext(false, true);
        $this->assertEquals(1, $res['index']);

        $res = $action->getExposedValidatedContext(false, true);
        $this->assertEquals(1, $res['index']);
    }
}
