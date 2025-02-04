<?php

namespace Comhon\CustomAction\Models;

use Comhon\CustomAction\Exceptions\InvalidEventTypeException;
use Comhon\CustomAction\Facades\CustomActionModelResolver;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class EventListener extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'custom_event_listeners';

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'scope' => 'array',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'scope',
    ];

    public function eventActions(): HasMany
    {
        return $this->hasMany(EventAction::class, 'event_listener_id');
    }

    public function getEventClass(): string
    {
        return CustomActionModelResolver::getClass($this->event)
            ?? throw new InvalidEventTypeException($this);
    }
}
