<?php

namespace App\Policies\CustomAction;

use App\Models\User;
use Comhon\CustomAction\Models\ManualAction;

class ManualActionPolicy
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
    public function view(User $user, ManualAction $model)
    {
        // TODO put your authorization logic here
    }

    /**
     * Determine whether the user can simulate action.
     */
    public function simulate(User $user, ManualAction $model)
    {
        // TODO put your authorization logic here
    }
}
