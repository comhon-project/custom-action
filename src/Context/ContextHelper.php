<?php

namespace Comhon\CustomAction\Context;

use Comhon\CustomAction\Contracts\HasContextInterface;
use Comhon\CustomAction\Facades\ContextFinder;
use Illuminate\Support\Arr;

class ContextHelper
{
    /**
     * get all context keys from given event that have given types.
     */
    public static function getEventContextRules(string $eventClass, array $types): array
    {
        if (! is_subclass_of($eventClass, HasContextInterface::class)) {
            throw new \InvalidArgumentException('first argument must be a subclass of HasContextInterface');
        }
        $contextRules = [];
        $contextSchema = $eventClass::getContextSchema();
        foreach ($types as $key => $type) {
            $asArray = false;
            if (strpos($type, 'array:') === 0) {
                $type = substr($type, 6);
                $asArray = true;
            }
            $enum = ContextFinder::find($type, $contextSchema);
            if (! empty($enum)) {
                if ($asArray) {
                    $contextRules[$key] = 'array';
                    $key .= '.*';
                }
                $contextRules[$key] = 'string|in:'.implode(',', $enum);
            }
        }

        return $contextRules;
    }

    /**
     * get all values values from given key
     */
    public static function getValues(array $data, string $key): array
    {
        if (strpos($key, '*') === false) {
            return [Arr::get($data, $key)];
        }
        $values = [];

        $retrieveValuesRecursive = function (&$values, $data, $path, $level) use (&$retrieveValuesRecursive) {
            $currentKey = $path[$level] ?? null;
            if (! isset($currentKey)) {
                $values[] = $data;

                return;
            }
            if (! Arr::accessible($data)) {
                return;
            }
            if ($currentKey == '*') {
                foreach ($data as $subValue) {
                    $retrieveValuesRecursive($values, $subValue, $path, $level + 1);
                }
            } elseif (Arr::exists($data, $currentKey)) {
                $retrieveValuesRecursive($values, $data[$currentKey], $path, $level + 1);
            }
        };

        $retrieveValuesRecursive($values, $data, explode('.', $key), 0);

        return $values;
    }
}
