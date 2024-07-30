<?php

namespace App\Policies\CustomAction;

use App\Models\User;
use Comhon\CustomAction\Models\EventListener;

class EventListenerPolicy
{
    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, EventListener $model)
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
    public function update(User $user, EventListener $model)
    {
        // TODO put your authorization logic here
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, EventListener $model)
    {
        // TODO put your authorization logic here
    }
}
