<?php

namespace Database\Factories;

use Comhon\CustomAction\Models\ActionScopedSettings;
use Comhon\CustomAction\Models\ActionSettings;
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
            'scope' => [],
            'settings' => [],
        ];
    }

    public function withEventAction(?string $type = null, ?string $event = null): Factory
    {
        return $this->afterMaking(function (ActionScopedSettings $actionScopedSettings) use ($type, $event) {
            $actionScopedSettings->actionSettings()->associate(
                ActionSettings::factory()->withEventAction($type, $event)->create()
            );
        });
    }

    public function withManualAction(?string $type = null): Factory
    {
        return $this->afterMaking(function (ActionScopedSettings $actionScopedSettings) use ($type) {
            $actionScopedSettings->actionSettings()->associate(
                ActionSettings::factory()->withManualAction($type)->create()
            );
        });
    }
}
