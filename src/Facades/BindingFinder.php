<?php

namespace Comhon\CustomAction\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static array find(string $bindingType, array $bindingSchema)
 *
 * @see \Comhon\CustomAction\BindingFinder
 */
class BindingFinder extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Comhon\CustomAction\Contracts\BindingFinderInterface::class;
    }
}
