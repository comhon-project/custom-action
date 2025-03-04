<?php

declare(strict_types=1);

namespace App\Actions;

use Comhon\CustomAction\Actions\InteractWithBindingsTrait;
use Comhon\CustomAction\Actions\InteractWithSettingsTrait;
use Comhon\CustomAction\Contracts\CustomActionInterface;
use Comhon\CustomAction\Contracts\HasBindingsInterface;
use Comhon\CustomAction\Models\Action;
use Comhon\CustomAction\Models\ManualAction;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class TestActionCache implements CustomActionInterface, HasBindingsInterface
{
    use Dispatchable,
        InteractsWithQueue,
        InteractWithBindingsTrait,
        InteractWithSettingsTrait,
        Queueable,
        SerializesModels;

    private $index = 0;

    /**
     * Dispatch the action with the given arguments.
     */
    public function getAction(): Action
    {
        return ManualAction::first();
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
     * Get action binding schema.
     *
     * Common bindings + recipient specific bindings
     */
    final public static function getBindingSchema(): array
    {
        return [
            'index' => 'integer',
        ];
    }

    public function getBindingValues(?string $locale = null): array
    {
        return [
            'index' => ++$this->index,
        ];
    }

    /**
     * execute action
     */
    public function handle(): void
    {
        //
    }
}
