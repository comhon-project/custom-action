<?php

namespace Tests\Unit;

use Comhon\CustomAction\ActionSettings\SettingsContainerSelector;
use Comhon\CustomAction\Models\ManualAction;
use Tests\TestCase;

class SettingsContainerSelectorTest extends TestCase
{
    public function testSelectWithMissingActionSettings()
    {
        $this->expectExceptionMessage('missing action settings');
        SettingsContainerSelector::select(ManualAction::factory()->create(), []);
    }
}
