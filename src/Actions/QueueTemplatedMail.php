<?php

namespace Comhon\CustomAction\Actions;

class QueueTemplatedMail extends SendTemplatedMail
{
    /**
     * Indicates if the mail should be queued.
     *
     * @var bool
     */
    protected $shouldQueue = true;
}
