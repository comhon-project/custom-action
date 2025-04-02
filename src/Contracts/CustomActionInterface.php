<?php

namespace Comhon\CustomAction\Contracts;

use Comhon\CustomAction\Models\Action;

interface CustomActionInterface
{
    /**
     * Get action model
     */
    public function getActionModel(): Action;

    /**
     * Get action settings schema
     */
    public static function getSettingsSchema(?string $eventClassContext = null): array;

    /**
     * Get action localized settings schema
     */
    public static function getLocalizedSettingsSchema(?string $eventClassContext = null): array;
}
