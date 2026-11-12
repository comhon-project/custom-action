<?php

declare(strict_types=1);

namespace App\Actions;

use Comhon\CustomAction\Resolver\CustomActionModelResolver;

class FailingEventAction extends SimpleEventAction
{
    public function handle(CustomActionModelResolver $resolver)
    {
        // write in database before failing
        parent::handle($resolver);

        throw new \Exception('failing event action');
    }
}
