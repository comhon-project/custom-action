<?php

namespace Comhon\CustomAction\Contracts;

interface CustomActionInterface
{
    /**
     * vefify if action concern a targeted user
     *
     * @return bool
     */
    public function hasTargetUser(): bool;

    /**
     * Get action settings schema
     *
     * @return array
     */
    public function getSettingsSchema(): array;
    /**
     * Get action localized settings schema
     *
     * @return array
     */
    public function getLocalizedSettingsSchema(): array;

    /**
     * Get action binding schema
     *
     * @return array
     */
    public function getBindingSchema(): array;
}
