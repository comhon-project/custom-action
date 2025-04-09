<?php

namespace Comhon\CustomAction\Contracts;

interface ExposeContextInterface
{
    /**
     * Get event context schema
     */
    public static function getContextSchema(): array;
}
