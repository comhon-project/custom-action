<?php

namespace App\Policies\CustomAction;

use App\Models\User;
use Comhon\CustomAction\Models\ActionScopedSettings;
use Comhon\CustomAction\Models\ActionSettings;

class ActionScopedSettingsPolicy
{
    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ActionScopedSettings $model)
    {
        return $user->has_consumer_ability == true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, ActionSettings $actionSettings)
    {
        return $user->has_consumer_ability == true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ActionScopedSettings $model)
    {
        return $user->has_consumer_ability == true;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ActionScopedSettings $model)
    {
        return $user->has_consumer_ability == true;
    }
}
