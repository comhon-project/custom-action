<?php

namespace Database\Factories;

use Comhon\CustomAction\Models\CustomActionSettings;
use Comhon\CustomAction\Models\CustomManualAction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Comhon\CustomAction\Models\CustomManualAction>
 */
class CustomManualActionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = CustomManualAction::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'type' => 'send-company-email',
            'action_settings_id' => CustomActionSettings::factory(),
        ];
    }

    /**
     * registration company mail.
     */
    public function sendMailRegistrationCompany(?array $toOtherUserIds = null, $withScopedSettings = false, $withAttachement = false): Factory
    {
        return $this->state(function (array $attributes) use ($toOtherUserIds, $withScopedSettings, $withAttachement) {
            return [
                'type' => 'send-company-email',
                'action_settings_id' => CustomActionSettings::factory()->sendMailRegistrationCompany($toOtherUserIds, $withScopedSettings, $withAttachement),
            ];
        });
    }
}
