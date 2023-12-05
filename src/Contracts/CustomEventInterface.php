<?php

namespace Comhon\CustomAction\Contracts;

interface CustomEventInterface
{
    /**
     * Get actions that might be attached to event
     *
     * @return array
     */
    public static function getAllowedActions(): array;

    /**
     * Get event binding schema
     *
     * @return array
     */
    public static function getBindingSchema(): array;

    /**
     * Get event binding values
     *
     * @return array
     */
    public function getBindingValues(): array;
}
