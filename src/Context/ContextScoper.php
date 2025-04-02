<?php

namespace Comhon\CustomAction\Context;

use Comhon\CustomAction\Contracts\ContextScoperInterface;
use Comhon\CustomAction\Models\Action;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class ContextScoper implements ContextScoperInterface
{
    public function getEventListeners(Builder $query, array $context): Collection|array
    {
        $listeners = [];
        foreach ($query->lazyById() as $listener) {
            if ($this->match($listener, $context)) {
                $listeners[] = $listener;
            }
        }

        return $listeners;
    }

    public function getScopedSettings(Action $action, array $context): Collection|array
    {
        $possibleSettings = [];
        foreach ($action->scopedSettings()->lazyById() as $scopedSettings) {
            if ($this->match($scopedSettings, $context)) {
                $possibleSettings[] = $scopedSettings;
            }
        }

        return $possibleSettings;
    }

    private function match(object $container, array $context): bool
    {
        if ($container->scope) {
            foreach ($container->scope as $path => $value) {
                if (Arr::get($context, $path) != $value) {
                    return false;
                }
            }
        }

        return true;
    }
}
