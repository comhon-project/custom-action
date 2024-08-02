<?php

namespace Comhon\CustomAction\Support;

use Comhon\CustomAction\Contracts\HasBindingsInterface;
use Comhon\CustomAction\Facades\BindingsFinder;
use Illuminate\Support\Arr;

class Bindings
{
    /**
     * get all bindings keys from given event that have given types.
     */
    public static function getEventBindingRules(string $eventClass, array $types): array
    {
        if (! is_subclass_of($eventClass, HasBindingsInterface::class)) {
            throw new \InvalidArgumentException('first argument must be a subclass of HasBindingsInterface');
        }
        $bindingRules = [];
        $bindingSchema = $eventClass::getBindingSchema();
        foreach ($types as $key => $type) {
            $asArray = false;
            if (strpos($type, 'array:') === 0) {
                $type = substr($type, 6);
                $asArray = true;
            }
            $enum = BindingsFinder::find($type, $bindingSchema);
            if ($asArray) {
                $bindingRules[$key] = 'array';
                $key .= '.*';
            }
            $bindingRules[$key] = 'string|in:'.implode(',', $enum);
        }

        return $bindingRules;
    }

    /**
     * get all bindings values from given key
     */
    public static function getBindingValues(array $bindings, string $key): array
    {
        if (strpos($key, '*') === false) {
            return [Arr::get($bindings, $key)];
        }
        $values = [];

        $retrieveBindingRecursive = function (&$values, $bindings, $path, $level) use (&$retrieveBindingRecursive) {
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
                    $retrieveBindingRecursive($values, $subValue, $path, $level + 1);
                }
            } elseif (Arr::exists($bindings, $currentKey)) {
                $retrieveBindingRecursive($values, $bindings[$currentKey], $path, $level + 1);
            }
        };

        $retrieveBindingRecursive($values, $bindings, explode('.', $key), 0);

        return $values;
    }
}
