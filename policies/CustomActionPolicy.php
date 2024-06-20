<?php

namespace App\Policies\CustomAction;

use App\Models\User;
use Comhon\CustomAction\Contracts\CustomActionInterface;

class CustomActionPolicy
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
    public function view(User $user, CustomActionInterface $model)
    {
        // TODO put your authorization logic here
    }
}
