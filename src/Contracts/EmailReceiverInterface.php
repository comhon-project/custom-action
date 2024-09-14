<?php

namespace Comhon\CustomAction\Contracts;

interface EmailReceiverInterface
{
    /**
     * Get values that can be exposed in email
     */
    public function getExposableValues(): array;
}
