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
     * Determine whether the user can view the event schema.
     */
    public function viewSchema(User $user, string $eventUniqueName)
    {
        // TODO put your authorization logic here
    }

    /**
     * Determine whether the user can view the events listeners.
     */
    public function viewListeners(User $user, string $eventUniqueName)
    {
        // TODO put your authorization logic here
    }
}
