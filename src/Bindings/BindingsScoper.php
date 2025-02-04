<?php

namespace Comhon\CustomAction\Bindings;

use Comhon\CustomAction\Contracts\BindingsScoperInterface;
use Comhon\CustomAction\Models\Action;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class BindingsScoper implements BindingsScoperInterface
{
    public function getEventListeners(Builder $query, array $bindings): Collection|array
    {
        $listeners = [];
        foreach ($query->lazyById() as $listener) {
            if ($this->match($listener, $bindings)) {
                $listeners[] = $listener;
            }
        }

        return $listeners;
    }

    public function getScopedSettings(Action $action, array $bindings): Collection|array
    {
        $possibleSettings = [];
        foreach ($action->scopedSettings()->lazyById() as $scopedSettings) {
            if ($this->match($scopedSettings, $bindings)) {
                $possibleSettings[] = $scopedSettings;
            }
        }

        return $possibleSettings;
    }

    private function match(object $container, array $bindings): bool
    {
        if ($container->scope) {
            foreach ($container->scope as $modelName => $filter) {
                foreach ($filter as $property => $value) {
                    if (($bindings[$modelName][$property] ?? null) != $value) {
                        return false;
                    }
                }
            }
        }

        return true;
    }
}
