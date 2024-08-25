<?php

namespace Comhon\CustomAction\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static array find(string $bindingType, array $bindingSchema)
 *
 * @see \Comhon\CustomAction\Bindings\BindingsFinder
 */
class BindingsFinder extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Comhon\CustomAction\Contracts\BindingsFinderInterface::class;
    }
}
