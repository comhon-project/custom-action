<?php

namespace Comhon\CustomAction\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static array getValidatedContext(array $context, array $schemaContext)
 *
 * @see \Comhon\CustomAction\Context\ContextValidator
 */
class ContextValidator extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Comhon\CustomAction\Contracts\ContextValidatorInterface::class;
    }
}
