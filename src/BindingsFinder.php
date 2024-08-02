<?php

namespace Comhon\CustomAction;

use Comhon\CustomAction\Contracts\BindingsFinderInterface;
use Comhon\CustomAction\Facades\CustomActionModelResolver;
use Comhon\CustomAction\Rules\RuleHelper;

class BindingsFinder implements BindingsFinderInterface
{
    public function find(string $bindingType, array $bindingSchema): array
    {
        $founds = [];
        $bindingClass = CustomActionModelResolver::getClass($bindingType);

        foreach ($bindingSchema as $key => $value) {
            if (is_string($value)) {
                $value = explode('|', $value);
            }
            if (is_array($value)) {
                foreach ($value as $rule) {
                    if (is_string($rule) && ($rule == $bindingType || $this->isA($rule, $bindingClass))) {
                        $founds[] = $key;
                    }
                }
            }
        }

        return $founds;
    }

    private function isA(string $rule, ?string $bindingClass): bool
    {
        if (! $bindingClass) {
            return false;
        }
        $ruleIsPrefix = RuleHelper::getRuleName('is').':';
        if (strpos($rule, $ruleIsPrefix) !== 0) {
            return false;
        }
        $class = CustomActionModelResolver::getClass(
            explode(',', substr($rule, strlen($ruleIsPrefix)))[0]
        );

        return is_a($class, $bindingClass, true);
    }
}
