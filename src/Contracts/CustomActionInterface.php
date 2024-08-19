<?php

namespace Comhon\CustomAction\Contracts;

interface CustomActionInterface
{
    /**
     * Dispatch the action with the given arguments.
     */
    public static function dispatch(...$arguments);

    /**
     * Get action settings schema
     */
    public static function getSettingsSchema(?string $eventClassContext = null): array;

    /**
     * Get action localized settings schema
     */
    public static function getLocalizedSettingsSchema(?string $eventClassContext = null): array;
}
