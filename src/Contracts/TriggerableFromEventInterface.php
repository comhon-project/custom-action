<?php

namespace Comhon\CustomAction\Contracts;

use Comhon\CustomAction\Models\CustomActionSettings;

interface TriggerableFromEventInterface
{
    /**
     * @param \Comhon\CustomAction\Contracts\CustomEventInterface $event
     * @param \Comhon\CustomAction\Models\CustomActionSettings $customActionSettings
     * @param array $bindings
     */
    public function handleFromEvent(
        CustomEventInterface $event,
        CustomActionSettings $customActionSettings,
        array $bindings = null
    );
}
