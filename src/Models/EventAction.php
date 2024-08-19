<?php

namespace Comhon\CustomAction\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EventAction extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'custom_event_actions';

    public function eventListener(): BelongsTo
    {
        return $this->belongsTo(EventListener::class);
    }

    public function actionSettings(): BelongsTo
    {
        return $this->belongsTo(ActionSettings::class);
    }

    protected static function booted()
    {
        static::deleting(function (EventAction $eventAction) {
            $eventAction->actionSettings?->delete();
        });
    }
}