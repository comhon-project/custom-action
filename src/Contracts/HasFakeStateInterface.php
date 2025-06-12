<?php

namespace Comhon\CustomAction\Contracts;

interface HasFakeStateInterface
{
    /**
     * Return all values that may contain your state to build a context.
     */
    public static function getFakeStateSchema(): array;
}
