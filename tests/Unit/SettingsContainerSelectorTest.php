<?php

namespace Tests\Unit;

use Comhon\CustomAction\ActionSettings\SettingsContainerSelector;
use Comhon\CustomAction\Models\ManualAction;
use Tests\TestCase;

class SettingsContainerSelectorTest extends TestCase
{
    public function test_select_with_missing_action_settings()
    {
        $this->expectExceptionMessage('missing action settings');
        SettingsContainerSelector::select(ManualAction::factory()->create(), []);
    }
}
