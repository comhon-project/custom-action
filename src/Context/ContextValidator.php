<?php

namespace Comhon\CustomAction\Context;

use Comhon\CustomAction\Contracts\ContextValidatorInterface;
use Illuminate\Support\Facades\Validator;

class ContextValidator implements ContextValidatorInterface
{
    public function getValidatedContext(array $context, array $schemaContext): array
    {
        return Validator::validate($context, $schemaContext);
    }
}
