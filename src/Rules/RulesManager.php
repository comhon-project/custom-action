<?php

namespace Comhon\CustomAction\Rules;

use Comhon\TemplateRenderer\Rules\Template;

class RulesManager
{
    public static function getSettingsRules(array $schema, bool $addTargetRules = false): array
    {
        $rules = [
            'settings' => 'required|array',
        ];
        foreach ($schema as $key => $type) {
            if (strpos($type, 'array:') === 0) {
                $rules["settings.$key"] = 'array';
                $rules["settings.$key.*"] = self::getRuleType(str_replace('array:', '', $type));
            } else {
                $rules["settings.$key"] = self::getRuleType($type);
            }
        }
        if ($addTargetRules) {
            $rules['settings.to'] = 'array';
            $rules['settings.to.*'] = 'integer';
        }

        return $rules;
    }

    public static function getRuleType(string $schemaType): string|array
    {
        if ($schemaType == 'file') {
            return 'string';
        }
        if ($schemaType == 'template') {
            return [new Template()];
        }
        return $schemaType;
    }
}
