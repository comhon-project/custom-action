<?php

namespace App\Actions;

use Comhon\CustomAction\Actions\CallableManuallyTrait;
use Comhon\CustomAction\Actions\InteractWithContextTrait;
use Comhon\CustomAction\Actions\InteractWithSettingsTrait;
use Comhon\CustomAction\Contracts\CustomActionInterface;
use Comhon\CustomAction\Contracts\FakableInterface;
use Comhon\CustomAction\Services\ActionService;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class FakableNotSimulatabeAction implements CustomActionInterface, FakableInterface
{
    use CallableManuallyTrait,
        Dispatchable,
        InteractsWithQueue,
        InteractWithContextTrait,
        InteractWithSettingsTrait,
        Queueable,
        SerializesModels;

    public static function fake(?array $state = null): static
    {
        ActionService::ensureFakingSafe();

        return new static;
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
        // do nothing
    }
}
