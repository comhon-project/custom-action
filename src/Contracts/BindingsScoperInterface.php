<?php

namespace Comhon\CustomAction\Contracts;

use Comhon\CustomAction\Models\Action;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

interface BindingsScoperInterface
{
    /**
     * get event listeners according there scopes and given bindings.
     *
     * the given query target the model \Comhon\CustomAction\Models\EventListener
     * and has a filter on an event name
     */
    public function getEventListeners(Builder $query, array $bindings): Collection|array;

    /**
     * get action scoped settings according given action settings and given bindings.
     */
    public function getScopedSetting(Action $action, array $bindings): Collection|array;
}
