<?php

namespace Comhon\CustomAction\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static array find(string $type, array $contextSchema)
 *
 * @see \Comhon\CustomAction\Context\ContextFinder
 */
class ContextFinder extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Comhon\CustomAction\Contracts\ContextFinderInterface::class;
    }
}
