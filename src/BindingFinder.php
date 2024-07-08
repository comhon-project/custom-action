<?php

namespace Comhon\CustomAction;

use Comhon\CustomAction\Contracts\BindingFinderInterface;
use Comhon\CustomAction\Facades\CustomActionModelResolver;
use Comhon\CustomAction\Rules\RuleHelper;

class BindingFinder implements BindingFinderInterface
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
                    if (! is_string($rule)) {
                        continue;
                    }
                    if ($rule == $bindingType || $this->isSubclassOf($rule, $bindingClass)) {
                        $founds[] = $key;
                    }
                }
            }
        }

        return $founds;
    }

    private function isSubclassOf(string $rule, ?string $bindingClass): bool
    {
        if (! $bindingClass) {
            return false;
        }
        $ruleIsPrefix = RuleHelper::getRuleName('is').':';
        if (strpos($rule, $ruleIsPrefix) !== 0) {
            return false;
        }
        $class = CustomActionModelResolver::getClass(substr($rule, strlen($ruleIsPrefix)));

        return $class == $bindingClass || is_subclass_of($class, $bindingClass);
    }
}
