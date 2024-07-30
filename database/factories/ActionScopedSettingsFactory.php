<?php

namespace Database\Factories;

use Comhon\CustomAction\Models\ActionScopedSettings;
use Comhon\CustomAction\Models\ActionSettings;
use Comhon\CustomAction\Models\EventAction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Comhon\CustomAction\Models\ActionScopedSettings>
 */
class ActionScopedSettingsFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = ActionScopedSettings::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'name' => 'My Action Scoped Settings',
            'action_settings_id' => ActionSettings::factory(),
            'scope' => [],
            'settings' => [],
        ];
    }

    public function withEventActionType(string $type): Factory
    {
        return $this->afterMaking(function (ActionScopedSettings $actionScopedSettings) {
            if (! $actionScopedSettings->actionSettings) {
                $actionScopedSettings->actionSettings()->associate(ActionSettings::factory()->create());
            }
        })->afterCreating(function (ActionScopedSettings $actionScopedSettings) use ($type) {
            /** @var ActionSettings $actionSettings */
            $actionSettings = $actionScopedSettings->actionSettings;
            if (! $actionSettings->eventAction) {
                EventAction::factory([
                    'type' => $type,
                ])->for($actionSettings, 'actionSettings')->create();
            } else {
                $actionSettings->eventAction->type = $type;
                $actionSettings->eventAction->save();
            }
        });
    }

    public function withManualActionType(string $type): Factory
    {
        return $this->afterMaking(function (ActionScopedSettings $actionScopedSettings) {
            if (! $actionScopedSettings->actionSettings) {
                $actionScopedSettings->actionSettings()->associate(ActionSettings::factory()->create());
            }
        })->afterCreating(function (ActionScopedSettings $actionScopedSettings) use ($type) {
            /** @var ActionSettings $actionSettings */
            $actionSettings = $actionScopedSettings->actionSettings;
            if (! $actionSettings->manualAction) {
                EventAction::factory([
                    'type' => $type,
                ])->for($actionSettings, 'actionSettings')->create();
            } else {
                $actionSettings->manualAction->type = $type;
                $actionSettings->manualAction->save();
            }
        });
    }
}
