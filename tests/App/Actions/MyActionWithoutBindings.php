<?php

namespace App\Actions;

use Comhon\CustomAction\Contracts\BindingsContainerInterface;
use Comhon\CustomAction\Contracts\CustomActionInterface;
use Comhon\CustomAction\Models\ActionSettings;

class MyActionWithoutBindings implements CustomActionInterface
{
    public function handle(ActionSettings $actionSettings, ?BindingsContainerInterface $bindingsContainer = null)
    {
        return null;
    }

    public function getSettingsSchema(?string $eventClassContext = null): array
    {
        return [];
    }

    public function getLocalizedSettingsSchema(?string $eventClassContext = null): array
    {
        return [];
    }
}
