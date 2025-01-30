<?php

namespace App\Actions;

use Comhon\CustomAction\Contracts\BindingsContainerInterface;
use Comhon\CustomAction\Contracts\CustomActionInterface;
use Comhon\CustomAction\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Tests\Support\Caller;

class MyActionWithoutBindings implements CustomActionInterface
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected Setting $setting,
        protected ?BindingsContainerInterface $bindingsContainer = null,
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
        app(Caller::class)->call($this->setting, $this->bindingsContainer);
    }
}
