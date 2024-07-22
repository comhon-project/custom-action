<?php

namespace Database\Factories;

use Comhon\CustomAction\Models\CustomActionSettings;
use Comhon\CustomAction\Models\CustomEventAction;
use Comhon\CustomAction\Models\CustomEventListener;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Comhon\CustomAction\Models\CustomEventAction>
 */
class CustomEventActionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = CustomEventAction::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'type' => 'send-email',
            'event_listener_id' => CustomEventListener::factory(),
            'action_settings_id' => CustomActionSettings::factory(),
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
                'action_settings_id' => CustomActionSettings::factory()->sendMailRegistrationCompany($toOtherUserIds, $withScopedSettings, $withAttachement),
            ];
        });
    }
}