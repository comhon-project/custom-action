<?php

namespace App\Policies\CustomAction;

use App\Models\User;
use Comhon\CustomAction\Models\CustomActionSettings;
use Comhon\CustomAction\Models\CustomEventListener;

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
     * Determine whether the user can create the model.
     */
    public function create(User $user, CustomEventListener $eventListener)
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
