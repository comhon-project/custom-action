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
        $isInstanceOf = $uniqueName === $value;
        $mustBeSubclass = isset($parameters[1]) && ($parameters[1] == 'false' || $parameters[1] == '0');

        if (! $isInstanceOf) {
            $baseClass = CustomActionModelResolver::getClass($uniqueName);
            $valueClass = is_string($value) ? CustomActionModelResolver::getClass($value) : $value;
            $isInstanceOf = $baseClass && $valueClass && is_subclass_of($valueClass, $baseClass);
        } elseif ($mustBeSubclass) {
            $isInstanceOf = false;
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
