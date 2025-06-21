<?php

declare(strict_types=1);

namespace App\Actions;

use Comhon\CustomAction\Actions\CallableManuallyTrait;
use Comhon\CustomAction\Actions\InteractWithContextTrait;
use Comhon\CustomAction\Actions\InteractWithSettingsTrait;
use Comhon\CustomAction\Contracts\CustomActionInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class QueuedManualAction implements CustomActionInterface, ShouldQueue
{
    use CallableManuallyTrait,
        Dispatchable,
        InteractsWithQueue,
        InteractWithContextTrait,
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

    public function handle() {}
}
