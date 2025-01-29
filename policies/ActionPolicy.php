<?php

namespace App\Policies\CustomAction;

use App\Models\User;

class ActionPolicy
{
    /**
     * Determine whether the user can view the schema of an action.
     */
    public function viewSchema(User $user, string $type)
    {
        // TODO put your authorization logic here
    }
}
