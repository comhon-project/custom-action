<?php

namespace Database\Factories;

use Comhon\CustomAction\Models\DefaultSetting;
use Comhon\CustomAction\Models\LocalizedSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Comhon\CustomAction\Models\DefaultSetting>
 */
class DefaultSettingFactory extends Factory
{
    use SettingFactoryTrait;

    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = DefaultSetting::class;

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
        })->afterCreating(function (DefaultSetting $action) {
            LocalizedSetting::factory()->for($action, 'localizable')->emailSettings('en', true)->create();
            LocalizedSetting::factory()->for($action, 'localizable')->emailSettings('fr', true)->create();
        });
    }
}
