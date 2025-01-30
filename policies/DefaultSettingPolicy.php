<?php

namespace App\Policies\CustomAction;

use App\Models\User;
use Comhon\CustomAction\Models\Action;
use Comhon\CustomAction\Models\DefaultSetting;

class DefaultSettingPolicy
{
    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, DefaultSetting $model)
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
    public function update(User $user, DefaultSetting $model)
    {
        // TODO put your authorization logic here
    }
}
