<?php

namespace Comhon\CustomAction\Contracts;

use Comhon\CustomAction\Models\Action;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

interface ContextScoperInterface
{
    /**
     * get event listeners according there scopes and given context.
     *
     * the given query target the model \Comhon\CustomAction\Models\EventListener
     * and has a filter on an event name
     */
    public function getEventListeners(Builder $query, array $context): Collection|array;

    /**
     * get action scoped settings according given action settings and given context.
     */
    public function getScopedSettings(Action $action, array $context): Collection|array;
}
