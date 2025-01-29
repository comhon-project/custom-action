<?php

namespace Database\Factories;

use Comhon\CustomAction\Models\ActionLocalizedSettings;
use Comhon\CustomAction\Models\ActionSettings;
use Comhon\CustomAction\Models\ActionSettingsContainer;
use Comhon\CustomAction\Models\EventAction;
use Comhon\CustomAction\Models\EventListener;
use Comhon\CustomAction\Models\ManualAction;
use Illuminate\Database\Eloquent\Factories\Factory;

trait ActionSettingsContainerFactoryTrait
{
    public function withEventAction(?string $type = null, ?string $event = null): Factory
    {
        return $this->afterMaking(function (ActionSettingsContainer $actionSettings) use ($type, $event) {
            $stateAction = $type ? ['type' => $type] : [];
            $stateEvent = $event ? ['event' => $event] : [];
            $eventAction = EventAction::factory($stateAction)
                ->for(EventListener::factory($stateEvent), 'eventListener')
                ->create();
            $actionSettings->action()->associate($eventAction);
        });
    }

    public function withManualAction(?string $type = null): Factory
    {
        return $this->afterMaking(function (ActionSettingsContainer $actionSettings) use ($type) {
            $state = $type ? ['type' => $type] : [];
            $actionSettings->action()->associate(ManualAction::factory($state)->create());
        });
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
