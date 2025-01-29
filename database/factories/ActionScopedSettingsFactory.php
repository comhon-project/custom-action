<?php

namespace Database\Factories;

use Comhon\CustomAction\Models\ActionScopedSettings;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Comhon\CustomAction\Models\ActionScopedSettings>
 */
class ActionScopedSettingsFactory extends Factory
{
    use ActionSettingsContainerFactoryTrait;

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
}
