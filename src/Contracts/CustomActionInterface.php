<?php

namespace Comhon\CustomAction\Contracts;

interface CustomActionInterface
{
    /**
     * Get action settings schema
     */
    public function getSettingsSchema(?string $eventClassContext = null): array;

    /**
     * Get action localized settings schema
     */
    public function getLocalizedSettingsSchema(?string $eventClassContext = null): array;

    /**
     * Get action binding schema
     */
    public function getBindingSchema(): array;
}
