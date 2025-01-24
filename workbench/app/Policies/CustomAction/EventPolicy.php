<?php

namespace App\Policies\CustomAction;

use App\Models\User;

class EventPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user)
    {
        return $user->has_consumer_ability == true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, string $eventClass)
    {
        return $user->has_consumer_ability == true;
    }
}
