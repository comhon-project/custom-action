<?php

namespace Comhon\CustomAction\Contracts;

use Comhon\CustomAction\Models\ActionSettings;

interface TriggerableFromEventInterface
{
    public function handleFromEvent(CustomEventInterface $event, ActionSettings $actionSettings);
}
