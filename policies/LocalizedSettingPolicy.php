<?php

namespace App\Policies\CustomAction;

use App\Models\User;
use Comhon\CustomAction\Models\LocalizedSetting;
use Comhon\CustomAction\Models\Setting;

class LocalizedSettingPolicy
{
    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, LocalizedSetting $model)
    {
        // TODO put your authorization logic here
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, Setting $model)
    {
        // TODO put your authorization logic here
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, LocalizedSetting $model)
    {
        // TODO put your authorization logic here
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, LocalizedSetting $model)
    {
        // TODO put your authorization logic here
    }
}
