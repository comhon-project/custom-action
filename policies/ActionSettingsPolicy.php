<?php

namespace App\Policies\CustomAction;

use App\Models\User;
use Comhon\CustomAction\Models\Action;
use Comhon\CustomAction\Models\ActionSettings;

class ActionSettingsPolicy
{
    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ActionSettings $model)
    {
        // TODO put your authorization logic here
    }

    /**
     * Determine whether the user can create the model.
     */
    public function create(User $user, Action $model)
    {
        // TODO put your authorization logic here
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ActionSettings $model)
    {
        // TODO put your authorization logic here
    }
}
