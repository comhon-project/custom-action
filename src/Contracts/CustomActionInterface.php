<?php

namespace Comhon\CustomAction\Contracts;

use Comhon\CustomAction\Models\ActionSettings;

interface CustomActionInterface
{
    /**
     * handle action with given settings and bindings
     */
    public function handle(ActionSettings $actionSettings, ?BindingsContainerInterface $bindingsContainer = null);

    /**
     * Get action settings schema
     */
    public function getSettingsSchema(?string $eventClassContext = null): array;

    /**
     * Get action localized settings schema
     */
    public function getLocalizedSettingsSchema(?string $eventClassContext = null): array;
}
