<?php

namespace Database\Factories;

use Comhon\CustomAction\Models\ScopedSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Comhon\CustomAction\Models\ScopedSetting>
 */
class ScopedSettingFactory extends Factory
{
    use SettingFactoryTrait;

    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = ScopedSetting::class;

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
