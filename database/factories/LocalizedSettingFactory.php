<?php

namespace Database\Factories;

use Comhon\CustomAction\Models\DefaultSetting;
use Comhon\CustomAction\Models\LocalizedSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Comhon\CustomAction\Models\LocalizedSetting>
 */
class LocalizedSettingFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = LocalizedSetting::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'localizable_id' => null,
            'localizable_type' => null,
            'locale' => 'en',
            'settings' => [],
        ];
    }

    /**
     * Configure the model factory.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (LocalizedSetting $actionLocalizedSettings) {
            if (! $actionLocalizedSettings->localizable) {
                $actionLocalizedSettings->localizable()->associate(DefaultSetting::factory()->create());
            }
        });
    }
}
