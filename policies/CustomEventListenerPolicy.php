<?php

namespace App\Policies\CustomAction;

use App\Models\User;
use Comhon\CustomAction\Models\CustomActionSettings;
use Comhon\CustomAction\Models\CustomEventListener;

class CustomEventListenerPolicy
{
    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, CustomEventListener $model)
    {
        // TODO put your authorization logic here
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, string $eventClass)
    {
        // TODO put your authorization logic here
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, CustomEventListener $model)
    {
        // TODO put your authorization logic here
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, CustomEventListener $model)
    {
        // TODO put your authorization logic here
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function syncAction(User $user, CustomEventListener $model, CustomActionSettings $customActionSettings)
    {
        // TODO put your authorization logic here
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function removeAction(User $user, CustomEventListener $model, CustomActionSettings $customActionSettings)
    {
        // TODO put your authorization logic here
    }
}
