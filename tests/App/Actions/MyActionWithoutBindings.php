<?php

namespace App\Actions;

use Comhon\CustomAction\Contracts\BindingsContainerInterface;
use Comhon\CustomAction\Contracts\CustomActionInterface;
use Comhon\CustomAction\Models\ActionSettings;
use Illuminate\Foundation\Queue\Queueable;
use Tests\Support\Caller;

class MyActionWithoutBindings implements CustomActionInterface
{
    use Queueable;

    public function __construct(
        private ActionSettings $actionSettings,
        private ?BindingsContainerInterface $bindingsContainer = null,
    ) {
        //
    }

    public static function getSettingsSchema(?string $eventClassContext = null): array
    {
        return [];
    }

    public static function getLocalizedSettingsSchema(?string $eventClassContext = null): array
    {
        return [];
    }

    public function handle()
    {
        app(Caller::class)->call($this->actionSettings, $this->bindingsContainer);
    }
}
