<?php

namespace Database\Factories;

use Comhon\CustomAction\Models\DefaultSetting;
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
            'type' => 'send-automatic-email',
            'event_listener_id' => EventListener::factory(),
        ];
    }

    /**
     * registration company mail.
     */
    public function sendMailRegistrationCompany(?array $toOtherUserIds = null, $withScopedSettings = false, $shoudQueue = false, $withAttachement = false): Factory
    {
        return $this->aftermaking(function (EventAction $eventAction) use ($shoudQueue) {
            $eventAction->type = $shoudQueue ? 'queue-automatic-email' : 'send-automatic-email';
        })->afterCreating(function (EventAction $eventAction) use ($toOtherUserIds, $withScopedSettings, $withAttachement) {
            DefaultSetting::factory()->for($eventAction, 'action')
                ->sendMailRegistrationCompany($toOtherUserIds, $withAttachement)
                ->create();

            if ($withScopedSettings) {
                $this->sendMailRegistrationCompanyScoped($eventAction, $toOtherUserIds);
            }
        });
    }
}
