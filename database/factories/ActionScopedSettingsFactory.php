<?php

namespace Database\Factories;

use Comhon\CustomAction\Models\ActionScopedSettings;
use Comhon\CustomAction\Models\CustomActionSettings;
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
            'custom_action_settings_id' => CustomActionSettings::factory(),
            'scope' => [],
            'settings' => [],
        ];
    }
}
