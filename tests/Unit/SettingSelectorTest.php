<?php

namespace Tests\Unit;

use Comhon\CustomAction\ActionSettings\SettingSelector;
use Comhon\CustomAction\Models\ManualAction;
use Tests\TestCase;

class SettingSelectorTest extends TestCase
{
    public function test_select_with_missing_action_settings()
    {
        $this->expectExceptionMessage("missing setting on action Comhon\CustomAction\Models\ManualAction with type 'send-company-email'");
        SettingSelector::select(ManualAction::factory()->create(), []);
    }
}
