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
    public function withDefaultSettings(?array $defaultSettings = null, ?array $localizedSettingsByLocales = null): Factory
    {
        return $this->afterCreating(function (Action $action) use ($defaultSettings, $localizedSettingsByLocales) {
            $defaultSettings ??= $this->getSettings($action);
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

    public function withScopedSettings(?array $scope, ?array $defaultSettings = null, ?array $localizedSettingsByLocales = null): Factory
    {
        return $this->afterCreating(function (Action $action) use ($scope, $defaultSettings, $localizedSettingsByLocales) {
            $defaultSettings ??= $this->getSettings($action);
            $localizedSettingsByLocales ??= $this->getLocalizedSettings($action);
            $scope ??= $this->getScope($action);

            $scopedSetting = ScopedSetting::factory([
                'settings' => $defaultSettings,
                'scope' => $scope,
            ])
                ->for($action, 'action')
                ->create();

            foreach ($localizedSettingsByLocales as $locale => $localizedSettings) {
                LocalizedSetting::factory([
                    'locale' => $locale,
                    'settings' => $localizedSettings,
                ])->for($scopedSetting, 'localizable')->create();
            }
        });
    }

    private function getSettings(Action $action): array
    {
        $actionClass = CustomActionModelResolver::getClass($action->type);

        return match ($actionClass) {
            SimpleEventAction::class => ['text' => 'simple text'],
            QueuedEventAction::class => ['text' => 'simple text'],
            ComplexEventAction::class => ['text' => 'text to user {{ user.id }} {{ user.translation.translate() }}'],
            SimpleManualAction::class => ['text' => 'simple text'],
            QueuedManualAction::class => ['text' => 'simple text'],
            ComplexManualAction::class => ['text' => 'text to user {{ user.id }} {{ user.translation.translate() }}'],
        };
    }

    private function getLocalizedSettings(Action $action): array
    {
        $actionClass = CustomActionModelResolver::getClass($action->type);

        return match ($actionClass) {
            SimpleEventAction::class => ['en' => ['localized_text' => 'localized simple text']],
            QueuedEventAction::class => ['en' => ['localized_text' => 'localized simple text']],
            ComplexEventAction::class => ['en' => ['localized_text' => 'localized text to user {{ user.id }} {{ user.translation.translate() }}']],
            SimpleManualAction::class => ['en' => ['localized_text' => 'localized simple text']],
            QueuedManualAction::class => ['en' => ['localized_text' => 'localized simple text']],
            ComplexManualAction::class => ['en' => ['localized_text' => 'localized text to user {{ user.id }} {{ user.translation.translate() }}']],
        };
    }

    private function getScope(Action $action): array
    {
        $actionClass = CustomActionModelResolver::getClass($action->type);

        return match ($actionClass) {
            ComplexEventAction::class => ['user.name' => 'doe'],
            ComplexManualAction::class => ['user.name' => 'doe'],
        };
    }
}
