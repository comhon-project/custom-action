<?php

namespace Database\Factories;

use Comhon\CustomAction\Models\ScopedSetting;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends Factory<ScopedSetting>
 */
class ScopedSettingFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Model>
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
