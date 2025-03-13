<?php

namespace Comhon\CustomAction\Contracts;

interface HasBindingsInterface
{
    /**
     * Get event binding schema
     */
    public static function getBindingSchema(): array;

    /**
     * Get event binding values
     */
    public function getBindingValues(): array;
}
