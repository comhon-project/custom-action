<?php

namespace Comhon\CustomAction\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Illuminate\Support\Collection|array getEventListeners(\Illuminate\Database\Eloquent\Builder $query, array $context)
 * @method static \Illuminate\Support\Collection|array getScopedSettings(\Comhon\CustomAction\Models\Action $action, array $context)
 *
 * @see \Comhon\CustomAction\Context\ContextScoper
 */
class ContextScoper extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Comhon\CustomAction\Contracts\ContextScoperInterface::class;
    }
}
