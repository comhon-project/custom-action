<?php

namespace Comhon\CustomAction\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
            foreach ($eventListener->eventActions as $eventAction) {
                $eventAction->delete();
            }
        });
    }

    public function eventActions(): HasMany
    {
        return $this->hasMany(CustomEventAction::class, 'event_listener_id');
    }
}
