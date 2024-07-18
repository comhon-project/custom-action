<?php

namespace Comhon\CustomAction\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomEventAction extends Model
{
    use HasFactory;

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
