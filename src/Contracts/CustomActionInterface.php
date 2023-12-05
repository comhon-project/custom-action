<?php

namespace Comhon\CustomAction\Contracts;

interface CustomActionInterface
{
    /**
     * vefify if action concern a targeted user
     */
    public function hasTargetUser(): bool;

    /**
     * Get action settings schema
     */
    public function getSettingsSchema(): array;

    /**
     * Get action localized settings schema
     */
    public function getLocalizedSettingsSchema(): array;

    /**
     * Get action binding schema
     */
    public function getBindingSchema(): array;
}
