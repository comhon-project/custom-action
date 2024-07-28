<?php

namespace Comhon\CustomAction\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomEventAction extends Model
{
    use HasFactory;
    use SoftDeletes;

    public function eventListener(): BelongsTo
    {
        return $this->belongsTo(CustomEventListener::class);
    }

    public function actionSettings(): BelongsTo
    {
        return $this->belongsTo(CustomActionSettings::class);
    }

    protected static function booted()
    {
        static::deleting(function (CustomEventAction $eventAction) {
            $eventAction->actionSettings?->delete();
        });
    }
}
