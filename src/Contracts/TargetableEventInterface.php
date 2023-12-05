<?php

namespace Comhon\CustomAction\Contracts;

use Illuminate\Foundation\Auth\User;

interface TargetableEventInterface
{

    /**
     * Get the target of the event
     *
     * @return \Illuminate\Foundation\Auth\User
     */
    public function target(): User;
}
