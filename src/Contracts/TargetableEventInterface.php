<?php

namespace Comhon\CustomAction\Contracts;

use Illuminate\Foundation\Auth\User;

interface TargetableEventInterface
{
    /**
     * Get the target of the event
     */
    public function target(): User;
}
