<?php

namespace Comhon\CustomAction\Bindings;

use Comhon\CustomAction\Contracts\BindingsScoperInterface;
use Comhon\CustomAction\Models\ActionSettings;
use Comhon\CustomAction\Models\ActionSettingsContainer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class BindingsScoper implements BindingsScoperInterface
{
    public function getEventListeners(Builder $query, array $bindings): Collection|array
    {
        $listeners = [];
        foreach ($query->lazy() as $listener) {
            if ($this->match($listener, $bindings)) {
                $listeners[] = $listener;
            }
        }

        return $listeners;
    }

    public function getSettingsContainer(ActionSettings $actionSettings, array $bindings): ActionSettingsContainer
    {
        foreach ($actionSettings->scopedSettings as $scopedSettings) {
            if ($this->match($scopedSettings, $bindings)) {
                return $scopedSettings;
            }
        }

        return $actionSettings;
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
