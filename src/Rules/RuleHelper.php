<?php

namespace Comhon\CustomAction\Rules;

class RuleHelper
{
    public static function getRuleName(string $ruleName): string
    {
        return config('custom-action.rule_prefix').$ruleName;
    }

    public static function getSettingsRules(array $schema, $prefix = 'settings'): array
    {
        $rules = [
            $prefix => 'present|array',
        ];
        foreach ($schema as $key => $rule) {
            $rules["$prefix.$key"] = $rule;
        }

        return $rules;
    }
}
