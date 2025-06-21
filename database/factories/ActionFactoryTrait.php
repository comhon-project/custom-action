<?php

namespace Database\Factories;

use App\Actions\ComplexEventAction;
use App\Actions\ComplexManualAction;
use App\Actions\QueuedEventAction;
use App\Actions\QueuedManualAction;
use App\Actions\SimpleEventAction;
use App\Actions\SimpleManualAction;
use Comhon\CustomAction\Facades\CustomActionModelResolver;
use Comhon\CustomAction\Models\Action;
use Comhon\CustomAction\Models\DefaultSetting;
use Comhon\CustomAction\Models\LocalizedSetting;
use Comhon\CustomAction\Models\ScopedSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

trait ActionFactoryTrait
{
    /**
     * registration company mail.
     */
    public function sendMailRegistrationCompanyScoped(Action $action, ?array $toOtherUserIds = null)
    {
        $keyReceivers = $toOtherUserIds === null ? 'context' : 'static';
        $valueReceivers = $toOtherUserIds === null
            ? ['user']
            : collect($toOtherUserIds)->map(fn ($id) => ['recipient_type' => 'user', 'recipient_id' => $id])->all();

        ScopedSetting::factory([
            'scope' => ['company.name' => 'My VIP company'],
            'settings' => [
                'recipients' => ['to' => [$keyReceivers => ['mailables' => $valueReceivers]]],
            ],
        ])->for($action, 'action')
            ->has(LocalizedSetting::factory([
                'locale' => 'en',
                'settings' => [
                    'subject' => 'Dear {{ to.first_name }}, VIP company {{ company.name }} (verified at: {{ to.verified_at|format_datetime(\'long\', \'none\')|replace({\' \': " "}) }} ({{preferred_timezone}}))',
                    'body' => 'the VIP company <strong>{{ company.name }}</strong> has been registered !!!',
                ],
            ]))
            ->has(LocalizedSetting::factory([
                'locale' => 'fr',
                'settings' => [
                    'subject' => 'Cher·ère {{ to.first_name }}, société VIP {{ company.name }} (vérifié à: {{ to.verified_at|format_datetime(\'long\', \'none\')|replace({\' \': " "}) }} ({{preferred_timezone}}))',
                    'body' => 'la société VIP <strong>{{ company.name }}</strong> à été inscrite !!!',
                ],
            ]))
            ->create();
    }

    public function withSettings(?array $defaultSettings = null, ?array $localizedSettingsByLocales = null): Factory
    {
        return $this->afterCreating(function (Action $action) use ($defaultSettings, $localizedSettingsByLocales) {
            $defaultSettings ??= $this->getDefaultSettings($action);
            $localizedSettingsByLocales ??= $this->getLocalizedSettings($action);

            $defaultSetting = DefaultSetting::factory(['settings' => $defaultSettings])
                ->for($action, 'action')
                ->create();

            foreach ($localizedSettingsByLocales as $locale => $localizedSettings) {
                LocalizedSetting::factory([
                    'locale' => $locale,
                    'settings' => $localizedSettings,
                ])->for($defaultSetting, 'localizable')->create();
            }
        });
    }

    private function getDefaultSettings(Action $action): array
    {
        $actionClass = CustomActionModelResolver::getClass($action->type);

        return match ($actionClass) {
            SimpleEventAction::class => ['text' => 'simple text'],
            QueuedEventAction::class => ['text' => 'simple text'],
            ComplexEventAction::class => ['text' => 'text to user {{ user.id }} {{ user.status.translate() }}'],
            SimpleManualAction::class => ['text' => 'simple text'],
            QueuedManualAction::class => ['text' => 'simple text'],
            ComplexManualAction::class => ['text' => 'text to user {{ user.id }} {{ user.status.translate() }}'],
        };
    }

    private function getLocalizedSettings(Action $action): array
    {
        $actionClass = CustomActionModelResolver::getClass($action->type);

        return match ($actionClass) {
            SimpleEventAction::class => ['en' => ['localized_text' => 'localized simple text']],
            QueuedEventAction::class => ['en' => ['localized_text' => 'localized simple text']],
            ComplexEventAction::class => ['en' => ['localized_text' => 'localized text to user {{ user.id }} {{ user.status.translate() }}']],
            SimpleManualAction::class => ['en' => ['localized_text' => 'localized simple text']],
            QueuedManualAction::class => ['en' => ['localized_text' => 'localized simple text']],
            ComplexManualAction::class => ['en' => ['localized_text' => 'localized text to user {{ user.id }} {{ user.status.translate() }}']],
        };
    }
}
