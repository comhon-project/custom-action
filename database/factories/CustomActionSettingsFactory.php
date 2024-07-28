<?php

namespace Database\Factories;

use Comhon\CustomAction\Models\ActionLocalizedSettings;
use Comhon\CustomAction\Models\ActionScopedSettings;
use Comhon\CustomAction\Models\CustomActionSettings;
use Comhon\CustomAction\Models\CustomEventAction;
use Comhon\CustomAction\Models\CustomManualAction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Comhon\CustomAction\Models\CustomActionSettings>
 */
class CustomActionSettingsFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = CustomActionSettings::class;

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

    public function withEventActionType(string $type): Factory
    {
        return $this->afterCreating(function (CustomActionSettings $customActionSettings) use ($type) {
            if (! $customActionSettings->eventAction) {
                CustomEventAction::factory([
                    'type' => $type,
                ])->for($customActionSettings, 'actionSettings')->create();
            } else {
                $customActionSettings->eventAction->type = $type;
                $customActionSettings->eventAction->save();
            }
        });
    }

    public function withManualActionType(string $type): Factory
    {
        return $this->afterCreating(function (CustomActionSettings $customActionSettings) use ($type) {
            if (! $customActionSettings->manualAction) {
                CustomManualAction::factory([
                    'type' => $type,
                ])->for($customActionSettings, 'actionSettings')->create();
            } else {
                $customActionSettings->manualAction->type = $type;
                $customActionSettings->manualAction->save();
            }
        });
    }

    /**
     * registration company mail.
     */
    public function sendMailRegistrationCompany(?array $toOtherUserIds = null, $withScopedSettings = false, $withAttachement = false): Factory
    {
        $keyReceivers = $toOtherUserIds === null ? 'to_bindings_receivers' : 'to_receivers';
        $valueReceivers = $toOtherUserIds === null
            ? ['user']
            : collect($toOtherUserIds)->map(fn ($id) => ['receiver_type' => 'user', 'receiver_id' => $id])->all();

        return $this->state(function (array $attributes) use ($keyReceivers, $valueReceivers, $withAttachement) {
            return [
                'settings' => [
                    $keyReceivers => $valueReceivers,
                    'attachments' => $withAttachement ? ['logo'] : null,
                ],
            ];
        })->afterCreating(function (CustomActionSettings $action) use ($withScopedSettings, $keyReceivers, $valueReceivers) {

            ActionLocalizedSettings::factory()->for($action, 'localizable')->emailSettings('en', true)->create();
            ActionLocalizedSettings::factory()->for($action, 'localizable')->emailSettings('fr', true)->create();

            if ($withScopedSettings) {
                $scopedSettings = new ActionScopedSettings;
                $scopedSettings->actionSettings()->associate($action);
                $scopedSettings->name = 'my scoped settings';
                $scopedSettings->scope = ['company' => ['name' => 'My VIP company']];
                $scopedSettings->settings = [
                    $keyReceivers => $valueReceivers,
                ];
                $scopedSettings->save();

                $localizedSettings = new ActionLocalizedSettings;
                $localizedSettings->localizable()->associate($scopedSettings);
                $localizedSettings->locale = 'en';
                $localizedSettings->settings = [
                    'subject' => 'Dear {{ to.first_name }}, VIP company {{ company.name }} (verified at: {{ to.verified_at|format_datetime(\'long\', \'none\') }} ({{preferred_timezone}}))',
                    'body' => 'the VIP company <strong>{{ company.name }}</strong> has been registered !!!',
                ];
                $localizedSettings->save();

                $localizedSettings = new ActionLocalizedSettings;
                $localizedSettings->localizable()->associate($scopedSettings);
                $localizedSettings->locale = 'fr';
                $localizedSettings->settings = [
                    'subject' => 'Cher·ère {{ to.first_name }}, société VIP {{ company.name }} (vérifié à: {{ to.verified_at|format_datetime(\'long\', \'none\') }} ({{preferred_timezone}}))',
                    'body' => 'la société VIP <strong>{{ company.name }}</strong> à été inscrite !!!',
                ];
                $localizedSettings->save();
            }
        });
    }
}
