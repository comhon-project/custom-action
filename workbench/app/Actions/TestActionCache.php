<?php

declare(strict_types=1);

namespace App\Actions;

use Comhon\CustomAction\Actions\InteractWithContextTrait;
use Comhon\CustomAction\Actions\InteractWithSettingsTrait;
use Comhon\CustomAction\Contracts\CustomActionInterface;
use Comhon\CustomAction\Contracts\HasContextInterface;
use Comhon\CustomAction\Models\Action;
use Comhon\CustomAction\Models\ManualAction;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class TestActionCache implements CustomActionInterface, HasContextInterface
{
    use Dispatchable,
        InteractsWithQueue,
        InteractWithContextTrait,
        InteractWithSettingsTrait,
        Queueable,
        SerializesModels;

    private $index = 0;

    /**
     * Dispatch the action with the given arguments.
     */
    public function getActionModel(): Action
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
     * Get action context schema.
     *
     * Common context + recipient specific context
     */
    final public static function getContextSchema(): array
    {
        return [
            'index' => 'integer',
        ];
    }

    public function getContext(): array
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
