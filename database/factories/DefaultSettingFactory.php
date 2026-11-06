<?php

namespace Database\Factories;

use Comhon\CustomAction\Models\DefaultSetting;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends Factory<DefaultSetting>
 */
class DefaultSettingFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Model>
     */
    protected $model = DefaultSetting::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'settings' => [],
        ];
    }
}
