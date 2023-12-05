<?php

namespace Comhon\CustomAction\Contracts;

use Comhon\CustomAction\Models\CustomActionSettings;

interface TriggerableFromEventInterface
{
    public function handleFromEvent(
        CustomEventInterface $event,
        CustomActionSettings $customActionSettings,
        ?array $bindings = null
    );
}
