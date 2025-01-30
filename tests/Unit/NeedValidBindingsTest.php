<?php

namespace Tests\Unit;

use App\Actions\TestActionCache;
use Comhon\CustomAction\Bindings\BindingsContainer;
use Comhon\CustomAction\Models\DefaultSetting;
use Tests\TestCase;

class NeedValidBindingsTest extends TestCase
{
    private function getAction()
    {
        $increment = 1;
        $bindingsContainer = new BindingsContainer(function () use (&$increment) {
            return ['index' => $increment++];
        });

        return new TestActionCache(
            DefaultSetting::factory()->withManualAction()->create(),
            $bindingsContainer
        );
    }

    public function test_get_validated_bindings_without_cache()
    {
        $action = $this->getAction();
        $res = $action->getValidatedBindings('en');
        $this->assertEquals(1, $res['index']);

        $res = $action->getValidatedBindings('en');
        $this->assertEquals(2, $res['index']);
    }

    public function test_get_validated_bindings_with_cache()
    {
        $action = $this->getAction();
        $res = $action->getValidatedBindings('en', true);
        $this->assertEquals(1, $res['index']);

        $res = $action->getValidatedBindings('en', true);
        $this->assertEquals(1, $res['index']);
    }
}
