<?php

namespace Comhon\CustomAction\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EventAction extends Action
{
    use HasFactory;
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'custom_event_actions';

    public function getContextClass(): string
    {
        return $this->eventListener->getEventClass();
    }

    public function eventListener(): BelongsTo
    {
        return $this->belongsTo(EventListener::class);
    }
}
