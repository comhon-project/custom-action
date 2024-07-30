<?php

namespace Database\Factories;

use Comhon\CustomAction\Models\ActionSettings;
use Comhon\CustomAction\Models\EventAction;
use Comhon\CustomAction\Models\EventListener;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Comhon\CustomAction\Models\EventAction>
 */
class EventActionFactory extends Factory
{
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
            'type' => 'send-email',
            'event_listener_id' => EventListener::factory(),
            'action_settings_id' => ActionSettings::factory(),
        ];
    }

    /**
     * registration company mail.
     */
    public function sendMailRegistrationCompany(?array $toOtherUserIds = null, $withScopedSettings = false, $shoudQueue = false, $withAttachement = false): Factory
    {
        return $this->state(function (array $attributes) use ($toOtherUserIds, $withScopedSettings, $shoudQueue, $withAttachement) {
            $type = $shoudQueue ? 'queue-email' : 'send-email';

            return [
                'type' => $type,
                'action_settings_id' => ActionSettings::factory()->sendMailRegistrationCompany($toOtherUserIds, $withScopedSettings, $withAttachement),
            ];
        });
    }
}
