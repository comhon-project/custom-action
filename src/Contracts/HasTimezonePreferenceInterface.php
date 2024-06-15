<?php

namespace Comhon\CustomAction\Contracts;

interface HasTimezonePreferenceInterface
{
    /**
     * Get the preferred timezone of the entity.
     *
     * @return string|null
     */
    public function preferredTimezone();
}
