<?php

namespace App\Policies\CustomAction;

use App\Models\User;

class ActionPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user)
    {
        // TODO put your authorization logic here
    }

    /**
     * Determine whether the user can view the action.
     */
    public function view(User $user, string $actionClass)
    {
        // TODO put your authorization logic here
    }
}
