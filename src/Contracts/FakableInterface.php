<?php

namespace Comhon\CustomAction\Contracts;

interface FakableInterface
{
    /**
     * instanciate class with faked data
     */
    public static function fake(): static;
}
