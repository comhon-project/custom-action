<?php

namespace Comhon\CustomAction\Database\Factories;

use Comhon\CustomAction\Tests\Support\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Comhon\CustomAction\Tests\Support\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = User::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => str_replace("'", '', fake()->lastName()),
            'first_name' => str_replace("'", '', fake()->firstName()),
            'email' => $this->faker->unique()->safeEmail,
            'preferred_locale' => 'en',
            'preferred_timezone' => 'UTC',
            'last_login_at' => '2022-12-12T00:00:00Z',
            'verified_at' => '2022-11-11'
        ];
    }

    /**
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function preferredTimezone($timezone)
    {
        return $this->state(function (array $attributes) use ($timezone) {
            return [
                'preferred_timezone' => $timezone,
            ];
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function hasConsumerAbility()
    {
        return $this->state(function (array $attributes) {
            return [
                'has_consumer_ability' => true,
            ];
        });
    }
}
