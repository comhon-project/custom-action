<?php

namespace Comhon\CustomAction\Models;

use Comhon\CustomAction\Exceptions\InvalidActionTypeException;
use Comhon\CustomAction\Facades\CustomActionModelResolver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

abstract class Action extends Model
{
    public function defaultSetting(): MorphOne
    {
        return $this->MorphOne(DefaultSetting::class, 'action');
    }

    public function scopedSettings(): MorphMany
    {
        return $this->morphMany(ScopedSetting::class, 'action');
    }

    public function getActionClass(): string
    {
        return CustomActionModelResolver::getClass($this->type)
            ?? throw new InvalidActionTypeException($this);
    }
}
