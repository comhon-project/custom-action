<?php

namespace Comhon\CustomAction\Context;

use Comhon\CustomAction\Contracts\ExposeContextInterface;
use Comhon\CustomAction\Contracts\FormatContextInterface;
use Comhon\CustomAction\Facades\CustomActionModelResolver;
use Comhon\CustomAction\Rules\RuleHelper;
use Illuminate\Support\Arr;

class ContextHelper
{
    /**
     * Extract context from given object instance.
     */
    public static function extractContext(object $object): array
    {
        return $object instanceof FormatContextInterface
            ? $object->formatContext()
            : get_object_vars($object);
    }

    /**
     * Extract context schemas from given classes and merge them.
     *
     * @param  array  $classes  for each element extract context schema if element is a class that
     *                          implements ExposeContextInterface otherwise element is ignored
     */
    public static function mergeContextSchemas(array $classes): array
    {
        $contexts = array_map(
            fn ($class) => ($class && is_subclass_of($class, ExposeContextInterface::class)) ? $class::getContextSchema() : [],
            $classes,
        );

        return array_merge(...$contexts);
    }

    /**
     * For each type, return the list of context keys that have the same type.
     */
    public static function getContextKeyEnumRuleAccordingType(array $types, array $contextSchema, bool $addArrayRule = false): array
    {
        $contextRules = [];
        $enums = [];
        foreach ($types as $key => $type) {
            $enums[$type] ??= self::find($type, $contextSchema);
            if (! empty($enums[$type])) {
                if ($addArrayRule && substr($key, -2) == '.*') {
                    $contextRules[substr($key, 0, -2)] = 'array';
                }
                $contextRules[$key] = 'string|in:'.implode(',', $enums[$type]);
            }
        }

        return $contextRules;
    }

    public static function find(string $type, array $contextSchema): array
    {
        $founds = [];
        $class = CustomActionModelResolver::getClass($type);

        foreach ($contextSchema as $key => $rules) {
            if (is_string($rules)) {
                $rules = explode('|', $rules);
            }
            if (is_array($rules)) {
                foreach ($rules as $rule) {
                    if (is_string($rule) && ($rule == $type || static::isA($rule, $class))) {
                        $founds[] = $key;
                    }
                }
            }
        }

        return $founds;
    }

    private static function isA(string $rule, ?string $class): bool
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

    public static function setTranslationValues(array &$values, array $translations)
    {
        $accessValueRecursive = function (&$values, $path, $level, $translator) use (&$accessValueRecursive) {
            $currentKey = $path[$level] ?? null;
            if (! isset($currentKey)) {
                $values = new Translatable($values, $translator);
            }
            if (! Arr::accessible($values)) {
                return;
            }
            if ($currentKey == '*') {
                foreach ($values as &$subValue) {
                    $accessValueRecursive($subValue, $path, $level + 1, $translator);
                }
            } elseif (Arr::exists($values, $currentKey)) {
                $accessValueRecursive($values[$currentKey], $path, $level + 1, $translator);
            }
        };

        foreach ($translations as $key => $translator) {
            $accessValueRecursive($values, explode('.', $key), 0, $translator);
        }
    }
}
