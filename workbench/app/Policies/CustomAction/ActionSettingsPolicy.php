<?php

namespace App\Policies\CustomAction;

use App\Models\User;
use Comhon\CustomAction\Models\ActionSettings;

class ActionSettingsPolicy
{
    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ActionSettings $model)
    {
        return $user->has_consumer_ability == true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ActionSettings $model)
    {
        return $user->has_consumer_ability == true;
    }
}
