<?php

namespace Comhon\CustomAction\Contracts;

interface HasContextInterface
{
    /**
     * Get event context schema
     */
    public static function getContextSchema(): array;

    /**
     * Get event context values
     */
    public function getContext(): array;
}
