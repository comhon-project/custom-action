<?php

namespace Comhon\CustomAction\Contracts;

interface HasBindingsInterface
{
    /**
     * Get event context schema
     */
    public static function getBindingSchema(): array;

    /**
     * Get event context values
     */
    public function getContext(): array;
}
