<?php

namespace Comhon\CustomAction\Context;

use Comhon\CustomAction\Contracts\ContextFinderInterface;
use Comhon\CustomAction\Facades\CustomActionModelResolver;
use Comhon\CustomAction\Rules\RuleHelper;

class ContextFinder implements ContextFinderInterface
{
    public function find(string $type, array $contextSchema): array
    {
        $founds = [];
        $class = CustomActionModelResolver::getClass($type);

        foreach ($contextSchema as $key => $rules) {
            if (is_string($rules)) {
                $rules = explode('|', $rules);
            }
            if (is_array($rules)) {
                foreach ($rules as $rule) {
                    if (is_string($rule) && ($rule == $type || $this->isA($rule, $class))) {
                        $founds[] = $key;
                    }
                }
            }
        }

        return $founds;
    }

    private function isA(string $rule, ?string $class): bool
    {
        if (! $class) {
            return false;
        }
        $ruleIsPrefix = RuleHelper::getRuleName('is').':';
        if (strpos($rule, $ruleIsPrefix) !== 0) {
            return false;
        }
        $ruleClass = CustomActionModelResolver::getClass(
            explode(',', substr($rule, strlen($ruleIsPrefix)))[0]
        );

        return is_a($ruleClass, $class, true);
    }
}
