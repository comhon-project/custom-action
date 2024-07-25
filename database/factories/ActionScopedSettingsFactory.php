<?php

namespace Database\Factories;

use Comhon\CustomAction\Models\ActionScopedSettings;
use Comhon\CustomAction\Models\CustomActionSettings;
use Comhon\CustomAction\Models\CustomEventAction;
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
            'action_settings_id' => CustomActionSettings::factory(),
            'scope' => [],
            'settings' => [],
        ];
    }

    public function withEventActionType(string $type): Factory
    {
        return $this->afterMaking(function (ActionScopedSettings $actionScopedSettings) {
            if (! $actionScopedSettings->actionSettings) {
                $actionScopedSettings->actionSettings()->associate(CustomActionSettings::factory()->create());
            }
        })->afterCreating(function (ActionScopedSettings $actionScopedSettings) use ($type) {
            /** @var CustomActionSettings $customActionSettings */
            $customActionSettings = $actionScopedSettings->actionSettings;
            if (! $customActionSettings->eventAction) {
                CustomEventAction::factory([
                    'type' => $type,
                ])->for($customActionSettings, 'actionSettings')->create();
            } else {
                $customActionSettings->eventAction->type = $type;
                $customActionSettings->eventAction->save();
            }
        });
    }

    public function withUniqueActionType(string $type): Factory
    {
        return $this->afterMaking(function (ActionScopedSettings $actionScopedSettings) {
            if (! $actionScopedSettings->actionSettings) {
                $actionScopedSettings->actionSettings()->associate(CustomActionSettings::factory()->create());
            }
        })->afterCreating(function (ActionScopedSettings $actionScopedSettings) use ($type) {
            /** @var CustomActionSettings $customActionSettings */
            $customActionSettings = $actionScopedSettings->actionSettings;
            if (! $customActionSettings->uniqueAction) {
                CustomEventAction::factory([
                    'type' => $type,
                ])->for($customActionSettings, 'actionSettings')->create();
            } else {
                $customActionSettings->uniqueAction->type = $type;
                $customActionSettings->uniqueAction->save();
            }
        });
    }
}
