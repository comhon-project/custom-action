<?php

namespace App\Policies\CustomAction;

use App\Models\User;
use Comhon\CustomAction\Models\ActionLocalizedSettings;
use Comhon\CustomAction\Models\ActionSettingsContainer;

class ActionLocalizedSettingsPolicy
{
    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ActionLocalizedSettings $model)
    {
        // TODO put your authorization logic here
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, ActionSettingsContainer $model)
    {
        // TODO put your authorization logic here
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ActionLocalizedSettings $model)
    {
        // TODO put your authorization logic here
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ActionLocalizedSettings $model)
    {
        // TODO put your authorization logic here
    }
}
