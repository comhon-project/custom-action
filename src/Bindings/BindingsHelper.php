<?php

namespace Comhon\CustomAction\Bindings;

use Comhon\CustomAction\Contracts\HasBindingsInterface;
use Comhon\CustomAction\Facades\BindingsFinder;
use Illuminate\Support\Arr;

class BindingsHelper
{
    /**
     * get all bindings keys from given event that have given types.
     */
    public static function getEventContextRules(string $eventClass, array $types): array
    {
        if (! is_subclass_of($eventClass, HasBindingsInterface::class)) {
            throw new \InvalidArgumentException('first argument must be a subclass of HasBindingsInterface');
        }
        $contextRules = [];
        $contextSchema = $eventClass::getBindingSchema();
        foreach ($types as $key => $type) {
            $asArray = false;
            if (strpos($type, 'array:') === 0) {
                $type = substr($type, 6);
                $asArray = true;
            }
            $enum = BindingsFinder::find($type, $contextSchema);
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
     * get all bindings values from given key
     */
    public static function getValues(array $bindings, string $key): array
    {
        if (strpos($key, '*') === false) {
            return [Arr::get($bindings, $key)];
        }
        $values = [];

        $retrieveValuesRecursive = function (&$values, $bindings, $path, $level) use (&$retrieveValuesRecursive) {
            $currentKey = $path[$level] ?? null;
            if (! isset($currentKey)) {
                $values[] = $bindings;

                return;
            }
            if (! Arr::accessible($bindings)) {
                return;
            }
            if ($currentKey == '*') {
                foreach ($bindings as $subValue) {
                    $retrieveValuesRecursive($values, $subValue, $path, $level + 1);
                }
            } elseif (Arr::exists($bindings, $currentKey)) {
                $retrieveValuesRecursive($values, $bindings[$currentKey], $path, $level + 1);
            }
        };

        $retrieveValuesRecursive($values, $bindings, explode('.', $key), 0);

        return $values;
    }
}
