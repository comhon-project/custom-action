<?php

namespace Comhon\CustomAction\Actions;

use Comhon\CustomAction\Contracts\CustomEventInterface;
use Comhon\CustomAction\Facades\BindingFinder;
use Illuminate\Support\Arr;

trait EventBindingAwareTrait
{
    protected function getEventBindingRules(string $eventClass, array $types): array
    {
        if (! is_subclass_of($eventClass, CustomEventInterface::class)) {
            throw new \InvalidArgumentException('first argument must be a subclass of CustomEventInterface');
        }
        $bindingRules = [];
        $bindingSchema = $eventClass::getBindingSchema();
        foreach ($types as $key => $type) {
            $asArray = false;
            if (strpos($type, 'array:') === 0) {
                $type = substr($type, 6);
                $asArray = true;
            }
            $enum = BindingFinder::find($type, $bindingSchema);
            if ($asArray) {
                $bindingRules[$key] = 'array';
                $key .= '.*';
            }
            $bindingRules[$key] = 'string|in:'.implode(',', $enum);
        }

        return $bindingRules;
    }

    public function retrieveBindingAsList(array $bindings, string $key): array
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
