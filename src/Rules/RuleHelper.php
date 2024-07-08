<?php

namespace Comhon\CustomAction\Rules;

class RuleHelper
{
    public static function getRuleName(string $ruleName): string
    {
        return config('custom-action.rule_prefix').$ruleName;
    }

    public static function getSettingsRules(array $schema): array
    {
        $rules = [
            'settings' => 'required|array',
        ];
        foreach ($schema as $key => $rule) {
            $rules["settings.$key"] = $rule;
        }

        return $rules;
    }
}
