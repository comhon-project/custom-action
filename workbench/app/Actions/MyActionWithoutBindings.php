<?php

namespace App\Actions;

use Comhon\CustomAction\Actions\CallableFromEventTrait;
use Comhon\CustomAction\Actions\InteractWithBindingsTrait;
use Comhon\CustomAction\Actions\InteractWithSettingsTrait;
use Comhon\CustomAction\Contracts\CallableFromEventInterface;
use Comhon\CustomAction\Contracts\CustomActionInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Tests\Support\Caller;

class MyActionWithoutBindings implements CallableFromEventInterface, CustomActionInterface
{
    use CallableFromEventTrait,
        CallableFromEventTrait,
        Dispatchable,
        InteractsWithQueue,
        InteractWithBindingsTrait,
        InteractWithSettingsTrait,
        Queueable,
        SerializesModels;

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
        app(Caller::class)->call($this->getSetting(), $this->eventBindingsContainer);
    }
}
