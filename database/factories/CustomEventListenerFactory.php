<?php

namespace Comhon\CustomAction\Database\Factories;

use Comhon\CustomAction\Models\CustomActionSettings;
use Comhon\CustomAction\Models\CustomEventListener;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Comhon\CustomAction\Models\CustomEventListener>
 */
class CustomEventListenerFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = CustomEventListener::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'event' => 'company-registered',
            'scope' => null,
        ];
    }

    /**
     * company registration listener.
     */
    public function genericRegistrationCompany(
        $toOtherUserId = null,
        $companyNameScope = null,
        $shoudQueue = false,
        $withAttachement = false
    ): Factory {

        return $this->state(function (array $attributes) use ($companyNameScope) {
            return [
                'event' => 'company-registered',
                'scope' => $companyNameScope ? ['company' => ['name' => $companyNameScope]] : null,
            ];
        })->afterCreating(function (CustomEventListener $listener) use ($toOtherUserId, $shoudQueue, $withAttachement) {
            $listener->actions()->attach(
                CustomActionSettings::factory()->sendMailRegistrationCompany(null, true, false, $withAttachement)->create(),
                ['should_queue' => $shoudQueue],
            );
            if ($toOtherUserId) {
                $listener->actions()->attach(
                    CustomActionSettings::factory()->sendMailRegistrationCompany($toOtherUserId)->create(),
                    ['should_queue' => $shoudQueue]
                );
            }
        });
    }
}
