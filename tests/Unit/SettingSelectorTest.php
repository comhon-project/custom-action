<?php

namespace Tests\Unit;

use Comhon\CustomAction\ActionSettings\SettingSelector;
use Comhon\CustomAction\Models\ManualAction;
use Tests\TestCase;

class SettingSelectorTest extends TestCase
{
    public function test_select_with_missing_action_settings()
    {
        $this->expectExceptionMessage('missing default setting');
        SettingSelector::select(ManualAction::factory()->create(), []);
    }
}
