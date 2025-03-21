<?php

namespace Tests\Unit;

use Comhon\CustomAction\ActionSettings\SettingSelector;
use Comhon\CustomAction\Models\ManualAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingSelectorTest extends TestCase
{
    use RefreshDatabase;

    public function test_select_with_missing_action_settings()
    {
        $action = ManualAction::factory()->create();
        $this->expectExceptionMessage("missing setting on action Comhon\CustomAction\Models\ManualAction with id '$action->id'");
        SettingSelector::select($action, []);
    }

    public function test_select_with_no_bindings_and_missing_action_settings()
    {
        $action = ManualAction::factory()->create();
        $this->expectExceptionMessage("missing default setting on action Comhon\CustomAction\Models\ManualAction with id '$action->id'");
        SettingSelector::select($action, null);
    }
}
