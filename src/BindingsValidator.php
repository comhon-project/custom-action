<?php

namespace Comhon\CustomAction;

use Comhon\CustomAction\Contracts\BindingsValidatorInterface;
use Illuminate\Support\Facades\Validator;

class BindingsValidator implements BindingsValidatorInterface
{
    public function getValidatedBindings(array $bindings, array $schemaBindings): array
    {
        return Validator::validate($bindings, $schemaBindings);
    }
}
