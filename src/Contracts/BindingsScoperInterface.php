<?php

namespace Comhon\CustomAction\Contracts;

use Comhon\CustomAction\Models\ActionSettings;
use Comhon\CustomAction\Models\ActionSettingsContainer;
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
     * get settings container according given action settings and given bindings.
     *
     * the settings container may be either
     * - a scoped action settings (that belongs to given action settings)
     * - the given action settings if no scoped action settings is found
     */
    public function getSettingsContainer(ActionSettings $actionSettings, array $bindings): ActionSettingsContainer;
}
