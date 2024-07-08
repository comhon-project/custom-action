<?php

namespace Comhon\CustomAction\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static void bind(string $uniqueName, string $class)
 * @method static ?string getUniqueName(string $class)
 * @method static ?string getClass(string $uniqueName)
 * @method static \Comhon\ModelResolverContract\ModelResolverInterface getResolver()
 * @method static void register(array $bindings, bool $reset = false)
 * @method static void isAllowedAction(string $uniqueName)
 * @method static void isAllowedEvent(string $uniqueName)
 *
 * @see \Comhon\CustomAction\Resolver\CustomActionModelResolver
 */
class CustomActionModelResolver extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Comhon\CustomAction\Resolver\CustomActionModelResolver::class;
    }
}
