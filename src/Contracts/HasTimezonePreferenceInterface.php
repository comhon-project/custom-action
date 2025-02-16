<?php

namespace Comhon\CustomAction\Contracts;

interface HasTimezonePreferenceInterface
{
    /**
     * Get the preferred timezone of the entity.
     */
    public function preferredTimezone(): ?string;
}
