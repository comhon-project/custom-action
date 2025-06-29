<?php

namespace Database\Factories;

use App\Models\UserWithoutPreference;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserWithoutPreference>
 */
class UserWithoutPreferenceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = UserWithoutPreference::class;

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
            'last_login_at' => '2022-12-12 00:00:00',
            'verified_at' => '2022-11-11',
            'status' => 'foo',
            'translation' => 'foo',
        ];
    }
}
