<?php

namespace App\Policies\CustomAction;

use App\Models\User;
use Comhon\CustomAction\Models\ActionScopedSettings;
use Comhon\CustomAction\Models\CustomActionSettings;

class ActionScopedSettingsPolicy
{
    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ActionScopedSettings $model)
    {
        // TODO put your authorization logic here
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, CustomActionSettings $customActionSettings)
    {
        // TODO put your authorization logic here
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ActionScopedSettings $model)
    {
        // TODO put your authorization logic here
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ActionScopedSettings $model)
    {
        // TODO put your authorization logic here
    }
}
