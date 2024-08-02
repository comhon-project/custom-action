<?php

namespace Comhon\CustomAction\Contracts;

interface CustomEventInterface
{
    /**
     * Get actions that might be attached to event
     */
    public static function getAllowedActions(): array;
}
