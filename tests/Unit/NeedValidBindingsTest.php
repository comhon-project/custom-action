<?php

namespace Tests\Unit;

use App\Actions\TestActionCache;
use Comhon\CustomAction\Bindings\BindingsContainer;
use Comhon\CustomAction\Models\ActionSettings;
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
            ActionSettings::factory()->create(),
            $bindingsContainer
        );
    }

    public function testGetValidatedBindingsWithoutCache()
    {
        $action = $this->getAction();
        $res = $action->getValidatedBindings('en');
        $this->assertEquals(1, $res['index']);

        $res = $action->getValidatedBindings('en');
        $this->assertEquals(2, $res['index']);
    }

    public function testGetValidatedBindingsWithCache()
    {
        $action = $this->getAction();
        $res = $action->getValidatedBindings('en', true);
        $this->assertEquals(1, $res['index']);

        $res = $action->getValidatedBindings('en', true);
        $this->assertEquals(1, $res['index']);
    }
}
