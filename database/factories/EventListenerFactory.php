<?php

namespace Database\Factories;

use Comhon\CustomAction\Contracts\CustomEventInterface;
use Comhon\CustomAction\Facades\CustomActionModelResolver;
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
            'event' => 'my-simple-event',
            'name' => 'My Custom Event Listener',
            'scope' => null,
        ];
    }

    public function event(string $eventClass): Factory
    {
        if (! is_subclass_of($eventClass, CustomEventInterface::class)) {
            throw new \Exception('given event must be instance of CustomEventInterface');
        }

        return $this->state(function (array $attributes) use ($eventClass) {
            return [
                'event' => CustomActionModelResolver::getUniqueName($eventClass)
                    ?? throw new \Exception("event $eventClass not registered"),
            ];
        });
    }
}
