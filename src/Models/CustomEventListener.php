<?php

namespace Comhon\CustomAction\Models;

use Comhon\CustomAction\Contracts\CustomUniqueActionInterface;
use Comhon\CustomAction\Resolver\ModelResolverContainer;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomEventListener extends Model
{
    use HasFactory;

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'scope' => 'array',
    ];

    protected static function booted()
    {
        static::deleting(function (CustomEventListener $eventListener) {
            $actions = $eventListener->actions()->get(['custom_action_settings.id', 'custom_action_settings.type']);
            $eventListener->actions()->detach();

            foreach ($actions as $action) {
                /** @var ModelResolverContainer $resolver */
                $resolver = app(ModelResolverContainer::class);
                if (!is_subclass_of($resolver->getClass($action->type), CustomUniqueActionInterface::class)) {
                    $action->delete();
                }
            }
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\belongsToMany
     */
    public function actions()
    {
        return $this->belongsToMany(CustomActionSettings::class)->withPivot(['should_queue']);
    }
}
