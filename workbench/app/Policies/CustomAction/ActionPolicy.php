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
        return $user->has_consumer_ability == true;
    }

    /**
     * Determine whether the user can view the action.
     */
    public function view(User $user, string $actionClass)
    {
        return $user->has_consumer_ability == true;
    }
}
