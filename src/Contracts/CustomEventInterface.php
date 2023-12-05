<?php

namespace Comhon\CustomAction\Contracts;

interface CustomEventInterface
{
    /**
     * Get actions that might be attached to event
     */
    public static function getAllowedActions(): array;

    /**
     * Get event binding schema
     */
    public static function getBindingSchema(): array;

    /**
     * Get event binding values
     */
    public function getBindingValues(): array;
}
