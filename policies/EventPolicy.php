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
        // TODO put your authorization logic here
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, string $eventClass)
    {
        // TODO put your authorization logic here
    }
}
