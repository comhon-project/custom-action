<?php

namespace Comhon\CustomAction\Rules;

use Comhon\CustomAction\Facades\CustomActionModelResolver;
use Illuminate\Validation\Validator;

class IsInstanceOf
{
    public function validate(string $attribute, mixed $value, array $parameters, Validator $validator): bool
    {
        if (! isset($parameters[0])) {
            throw new \Exception('must have one parameter');
        }
        $uniqueName = $parameters[0];
        $mustBeSubclass = isset($parameters[1]) && ($parameters[1] == 'false' || $parameters[1] == '0');
        $allowString = isset($parameters[2]) && ($parameters[2] == 'true' || $parameters[2] == '1');
        $baseClass = CustomActionModelResolver::getClass($uniqueName);
        $isInstanceOf = false;

        if ($allowString && is_string($value)) {
            $value = CustomActionModelResolver::getClass($value);
        }

        if ($baseClass && $value) {
            $isInstanceOf = $mustBeSubclass
                ? is_subclass_of($value, $baseClass, $allowString)
                : is_a($value, $baseClass, $allowString);
        }

        if (! $isInstanceOf) {
            $part = $mustBeSubclass ? 'subclass' : 'instance';
            $validator->setFallbackMessages([
                RuleHelper::getRuleName('is') => "The :attribute is not $part of $uniqueName.",
            ]);
        }

        return $isInstanceOf;
    }
}
