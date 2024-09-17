<?php

namespace Database\Factories;

use Comhon\CustomAction\Models\ActionLocalizedSettings;
use Comhon\CustomAction\Models\ActionScopedSettings;
use Comhon\CustomAction\Models\ActionSettings;
use Comhon\CustomAction\Models\EventAction;
use Comhon\CustomAction\Models\EventListener;
use Comhon\CustomAction\Models\ManualAction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Comhon\CustomAction\Models\ActionSettings>
 */
class ActionSettingsFactory extends Factory
{
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

    public function withEventActionType(?string $type = null, ?string $event = null): Factory
    {
        return $this->afterMaking(function (ActionSettings $actionSettings) use ($type, $event) {
            $stateAction = $type ? ['type' => $type] : [];
            $stateEvent = $event ? ['event' => $event] : [];
            $eventAction = EventAction::factory($stateAction)
                ->for(EventListener::factory($stateEvent), 'eventListener')
                ->create();
            $actionSettings->action()->associate($eventAction);
        });
    }

    public function withManualActionType(?string $type = null): Factory
    {
        return $this->afterMaking(function (ActionSettings $actionSettings) use ($type) {
            $state = $type ? ['type' => $type] : [];
            $actionSettings->action()->associate(ManualAction::factory($state)->create());
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
        })->afterCreating(function (ActionSettings $action) use ($withScopedSettings, $keyReceivers, $valueReceivers) {

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
                    'subject' => 'Dear {{ to.first_name }}, VIP company {{ company.name }} {{ localized }} (verified at: {{ to.verified_at|format_datetime(\'long\', \'none\')|replace({\' \': " "}) }} ({{preferred_timezone}}))',
                    'body' => 'the VIP company <strong>{{ company.name }}</strong> has been registered !!!',
                ];
                $localizedSettings->save();

                $localizedSettings = new ActionLocalizedSettings;
                $localizedSettings->localizable()->associate($scopedSettings);
                $localizedSettings->locale = 'fr';
                $localizedSettings->settings = [
                    'subject' => 'Cher·ère {{ to.first_name }}, société VIP {{ company.name }} {{ localized }} (vérifié à: {{ to.verified_at|format_datetime(\'long\', \'none\')|replace({\' \': " "}) }} ({{preferred_timezone}}))',
                    'body' => 'la société VIP <strong>{{ company.name }}</strong> à été inscrite !!!',
                ];
                $localizedSettings->save();
            }
        });
    }
}
