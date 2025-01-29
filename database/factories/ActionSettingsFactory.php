<?php

namespace Database\Factories;

use Comhon\CustomAction\Models\ActionLocalizedSettings;
use Comhon\CustomAction\Models\ActionSettings;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Comhon\CustomAction\Models\ActionSettings>
 */
class ActionSettingsFactory extends Factory
{
    use ActionSettingsContainerFactoryTrait;

    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = ActionSettings::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'settings' => [],
        ];
    }

    /**
     * registration company mail.
     */
    public function sendMailRegistrationCompany(?array $toOtherUserIds = null, $withAttachement = false): Factory
    {
        $keyReceivers = $toOtherUserIds === null ? 'bindings' : 'static';
        $valueReceivers = $toOtherUserIds === null
            ? ['user']
            : collect($toOtherUserIds)->map(fn ($id) => ['recipient_type' => 'user', 'recipient_id' => $id])->all();

        return $this->state(function (array $attributes) use ($keyReceivers, $valueReceivers, $withAttachement) {
            return [
                'settings' => [
                    'recipients' => ['to' => [$keyReceivers => ['mailables' => $valueReceivers]]],
                    'attachments' => $withAttachement ? ['logo'] : null,
                ],
            ];
        })->afterCreating(function (ActionSettings $action) {
            ActionLocalizedSettings::factory()->for($action, 'localizable')->emailSettings('en', true)->create();
            ActionLocalizedSettings::factory()->for($action, 'localizable')->emailSettings('fr', true)->create();
        });
    }
}
