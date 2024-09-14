<?php

namespace App\Actions;

use Comhon\CustomAction\Actions\InteractWithBindingsTrait;
use Comhon\CustomAction\Actions\InteractWithLocalizedSettingsTrait;
use Comhon\CustomAction\Contracts\BindingsContainerInterface;
use Comhon\CustomAction\Contracts\CustomActionInterface;
use Comhon\CustomAction\Models\ActionSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class TestActionCache implements CustomActionInterface
{
    use Dispatchable,
        InteractsWithQueue,
        InteractWithBindingsTrait,
        InteractWithLocalizedSettingsTrait,
        Queueable,
        SerializesModels;

    public function __construct(
        private ActionSettings $actionSettings,
        private ?BindingsContainerInterface $bindingsContainer = null,
    ) {
        //
    }

    /**
     * Get action settings schema
     */
    public static function getSettingsSchema(?string $eventClassContext = null): array
    {
        return [];
    }

    /**
     * Get action localized settings schema
     */
    public static function getLocalizedSettingsSchema(?string $eventClassContext = null): array
    {
        return [];
    }

    /**
     * execute action
     */
    public function handle(): void
    {
        //
    }
}
