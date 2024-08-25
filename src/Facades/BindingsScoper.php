<?php

namespace Comhon\CustomAction\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Illuminate\Support\Collection|array getEventListeners(\Illuminate\Database\Eloquent\Builder $query, array $bindings)
 * @method static \Comhon\CustomAction\Models\ActionSettingsContainer getSettingsContainer(\Comhon\CustomAction\Models\ActionSettings $actionSettings, array $bindings)
 *
 * @see \Comhon\CustomAction\Bindings\BindingsScoper
 */
class BindingsScoper extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Comhon\CustomAction\Contracts\BindingsScoperInterface::class;
    }
}
