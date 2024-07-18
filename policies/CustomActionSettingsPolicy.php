<?php

namespace App\Policies\CustomAction;

use App\Models\User;
use Comhon\CustomAction\Models\CustomActionSettings;

class CustomActionSettingsPolicy
{
    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, CustomActionSettings $model)
    {
        // TODO put your authorization logic here
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, CustomActionSettings $model)
    {
        // TODO put your authorization logic here
    }
}
