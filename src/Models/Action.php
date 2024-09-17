<?php

namespace Comhon\CustomAction\Models;

use Comhon\CustomAction\Exceptions\InvalidActionTypeException;
use Comhon\CustomAction\Facades\CustomActionModelResolver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;

abstract class Action extends Model
{
    public function actionSettings(): MorphOne
    {
        return $this->MorphOne(ActionSettings::class, 'action');
    }

    public function getActionClass(): string
    {
        return CustomActionModelResolver::getClass($this->type)
            ?? throw new InvalidActionTypeException("Invalid action type $this->type");
    }
}
