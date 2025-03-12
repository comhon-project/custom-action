<?php

namespace Database\Factories;

use Comhon\CustomAction\Models\EventAction;
use Comhon\CustomAction\Models\EventListener;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Comhon\CustomAction\Models\EventListener>
 */
class EventListenerFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = EventListener::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'event' => 'company-registered',
            'name' => 'My Custom Event Listener',
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
                'scope' => $companyNameScope ? ['company' => ['name' => $companyNameScope]] : null,
            ];
        })->afterCreating(function (EventListener $listener) use ($toOtherUserId, $shoudQueue, $withAttachement) {
            EventAction::factory()
                ->sendMailRegistrationCompany(null, true, $shoudQueue, $withAttachement)
                ->for($listener, 'eventListener')->create();

            if ($toOtherUserId) {
                EventAction::factory()
                    ->sendMailRegistrationCompany($toOtherUserId, false, $shoudQueue)
                    ->for($listener, 'eventListener')->create();
            }
        });
    }

    public function withBindingsTranslations(): Factory
    {
        return $this->afterMaking(function (EventListener $eventListener) {
            $eventListener->event = 'company-registered-with-bindings-translations';
        });
    }
}
