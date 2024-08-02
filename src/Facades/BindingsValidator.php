<?php

namespace Comhon\CustomAction\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static array getValidatedBindings(array $bindings, array $schemaBindings)
 *
 * @see \Comhon\CustomAction\BindingsValidator
 */
class BindingsValidator extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Comhon\CustomAction\Contracts\BindingsValidatorInterface::class;
    }
}
