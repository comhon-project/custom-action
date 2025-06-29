<?php

namespace Database\Factories;

use App\Actions\ComplexEventAction;
use App\Actions\QueuedEventAction;
use App\Actions\SimpleEventAction;
use App\Events\MyComplexEvent;
use App\Events\MySimpleEvent;
use Comhon\CustomAction\Contracts\CallableFromEventInterface;
use Comhon\CustomAction\Facades\CustomActionModelResolver;
use Comhon\CustomAction\Models\EventAction;
use Comhon\CustomAction\Models\EventListener;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Comhon\CustomAction\Models\EventAction>
 */
class EventActionFactory extends Factory
{
    use ActionFactoryTrait;

    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = EventAction::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'name' => 'My Custom Event Action',
            'type' => 'simple-event-action',
            'event_listener_id' => EventListener::factory(),
        ];
    }

    public function action(string $actionClass, null|string|object $eventClassOrEventListener = null): Factory
    {
        if (! is_subclass_of($actionClass, CallableFromEventInterface::class)) {
            throw new \Exception('given action is an event action, must be a manual action');
        }

        return $this->state(function (array $attributes) use ($actionClass, $eventClassOrEventListener) {
            if (! $eventClassOrEventListener) {
                $eventClassOrEventListener = match ($actionClass) {
                    SimpleEventAction::class => MySimpleEvent::class,
                    QueuedEventAction::class => MySimpleEvent::class,
                    ComplexEventAction::class => MyComplexEvent::class,
                    SimpleEventAction::class => MySimpleEvent::class,
                };
            }

            $eventListenerValue = is_string($eventClassOrEventListener)
                ? EventListener::factory([
                    'event' => CustomActionModelResolver::getUniqueName($eventClassOrEventListener)
                        ?? throw new \Exception("event $eventClassOrEventListener not registered"),
                ])
                : $eventClassOrEventListener->id;

            return [
                'type' => CustomActionModelResolver::getUniqueName($actionClass)
                    ?? throw new \Exception("action $actionClass not registered"),
                'event_listener_id' => $eventListenerValue,
            ];
        });
    }
}
