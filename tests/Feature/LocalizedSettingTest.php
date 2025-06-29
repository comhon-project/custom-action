<?php

namespace Tests\Feature;

use App\Actions\ComplexEventAction;
use App\Actions\ComplexManualAction;
use App\Models\User;
use Comhon\CustomAction\Models\DefaultSetting;
use Comhon\CustomAction\Models\ScopedSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\SetUpWithModelRegistrationTrait;
use Tests\TestCase;

class LocalizedSettingTest extends TestCase
{
    use RefreshDatabase;
    use SetUpWithModelRegistrationTrait;

    #[DataProvider('providerLocalizedSetting')]
    public function test_list_action_localized_settings_success($settingClass, $fromEventAction)
    {
        $resource = $settingClass == DefaultSetting::class ? 'default-settings' : 'scoped-settings';
        $actionClass = $fromEventAction ? ComplexEventAction::class : ComplexManualAction::class;
        $setting = $settingClass == DefaultSetting::class
            ? $this->getActionDefaultSetting($actionClass)
            : $this->getActionScopedSetting($actionClass);

        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();
        $this->actingAs($user)->getJson("custom/{$resource}/{$setting->id}/localized-settings")
            ->assertJsonCount(1, 'data')
            ->assertJson([
                'data' => [
                    ['locale' => 'en'],
                ],
            ])->assertJsonMissingPath('data.0.settings');
    }

    #[DataProvider('providerLocalizedSetting')]
    public function test_list_action_localized_settings_forbidden($settingClass, $fromEventAction)
    {
        $resource = $settingClass == DefaultSetting::class ? 'default-settings' : 'scoped-settings';
        $actionClass = $fromEventAction ? ComplexEventAction::class : ComplexManualAction::class;
        $setting = $settingClass == DefaultSetting::class
            ? $this->getActionDefaultSetting($actionClass)
            : $this->getActionScopedSetting($actionClass);

        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user)->getJson("custom/{$resource}/{$setting->id}/localized-settings")
            ->assertForbidden();
    }

    public function test_get_action_localized_settings_success()
    {
        $setting = $this->getActionDefaultSetting(ComplexManualAction::class);
        $localizedSetting = $setting->localizedSettings()->firstOrFail();

        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();
        $this->actingAs($user)->getJson("custom/localized-settings/{$localizedSetting->id}")
            ->assertJson([
                'data' => [
                    'id' => $localizedSetting->id,
                    'locale' => 'en',
                    'settings' => $localizedSetting->settings,
                ],
            ]);
    }

    public function test_get_action_localized_settings_forbidden()
    {
        $setting = $this->getActionDefaultSetting(ComplexManualAction::class);
        $localizedSetting = $setting->localizedSettings()->firstOrFail();

        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user)->getJson("custom/localized-settings/{$localizedSetting->id}")
            ->assertForbidden();
    }

    #[DataProvider('providerLocalizedSetting')]
    public function test_store_action_localized_settings_success($settingClass, $fromEventAction)
    {
        $resource = $settingClass == DefaultSetting::class ? 'default-settings' : 'scoped-settings';
        $actionClass = $fromEventAction ? ComplexEventAction::class : ComplexManualAction::class;
        $setting = $settingClass == DefaultSetting::class
            ? $this->getActionDefaultSetting($actionClass, null, [])
            : $this->getActionScopedSetting($actionClass, null, null, []);

        $inputsList = [
            [
                'locale' => 'en',
                'settings' => [
                    'localized_text' => 'original text',
                ],
            ],
            [
                'locale' => 'fr',
                'settings' => [
                    'localized_text' => 'text original',
                ],
            ],
        ];

        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();

        foreach ($inputsList as $inputs) {
            $this->actingAs($user)->postJson("custom/{$resource}/{$setting->id}/localized-settings", $inputs)
                ->assertCreated()
                ->assertJsonStructure(['data' => ['id']])
                ->assertJson([
                    'data' => $inputs,
                ]);

            $localizedSetting = $setting->localizedSettings()->where('locale', $inputs['locale'])->first();
            $this->assertNotNull($localizedSetting);
            $this->assertArraySubset($inputs, $localizedSetting->toArray());
        }
        $this->assertEquals(2, $setting->localizedSettings()->count());
    }

    public function test_store_action_localized_settings_locale_already_exists()
    {
        $setting = $this->getActionDefaultSetting(ComplexManualAction::class);

        $inputs = [
            'locale' => 'en',
            'settings' => [
                'localized_text' => 'original text',
            ],
        ];

        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();
        $this->actingAs($user)->postJson("custom/default-settings/{$setting->id}/localized-settings", $inputs)
            ->assertUnprocessable()
            ->assertJson([
                'message' => "A localized setting is already stored with locale 'en'.",
            ]);
    }

    #[DataProvider('providerLocalizedSetting')]
    public function test_store_action_localized_settings_invalid_settings($settingClass, $fromEventAction)
    {
        $resource = $settingClass == DefaultSetting::class ? 'default-settings' : 'scoped-settings';
        $actionClass = $fromEventAction ? ComplexEventAction::class : ComplexManualAction::class;
        $setting = $settingClass == DefaultSetting::class
            ? $this->getActionDefaultSetting($actionClass, null, [])
            : $this->getActionScopedSetting($actionClass, null, null, []);

        $inputs = [
            'locale' => 'en',
            'settings' => [
                'localized_text' => ['array'],
            ],
        ];

        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();
        $this->actingAs($user)->postJson("custom/{$resource}/{$setting->id}/localized-settings", $inputs)
            ->assertUnprocessable()
            ->assertJson([
                'message' => 'The value settings.localized_text must be a string',
            ]);
    }

    #[DataProvider('providerLocalizedSetting')]
    public function test_store_action_localized_settings_forbidden($settingClass, $fromEventAction)
    {
        $resource = $settingClass == DefaultSetting::class ? 'default-settings' : 'scoped-settings';
        $actionClass = $fromEventAction ? ComplexEventAction::class : ComplexManualAction::class;
        $setting = $settingClass == DefaultSetting::class
            ? $this->getActionDefaultSetting($actionClass, null, [])
            : $this->getActionScopedSetting($actionClass, null, null, []);

        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user)->postJson("custom/{$resource}/{$setting->id}/localized-settings")
            ->assertForbidden();
    }

    #[DataProvider('providerLocalizedSetting')]
    public function test_update_action_localized_settings_success($settingClass, $fromEventAction)
    {
        $actionClass = $fromEventAction ? ComplexEventAction::class : ComplexManualAction::class;
        $setting = $settingClass == DefaultSetting::class
            ? $this->getActionDefaultSetting($actionClass)
            : $this->getActionScopedSetting($actionClass);

        $localizedSetting = $setting->localizedSettings()->firstOrFail();

        $inputs = [
            'locale' => 'es',
            'settings' => [
                'localized_text' => 'texto en espanol',
            ],
        ];

        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();
        $this->actingAs($user)->putJson("custom/localized-settings/{$localizedSetting->id}", $inputs)
            ->assertOk()
            ->assertJson([
                'data' => [
                    'id' => $localizedSetting->id,
                    ...$inputs,
                ],
            ]);

        $this->assertEquals(1, $setting->localizedSettings()->count());
        $this->assertArraySubset($inputs, $localizedSetting->refresh()->toArray());
    }

    #[DataProvider('providerLocalizedSetting')]
    public function test_update_action_localized_settings_forbidden($settingClass, $fromEventAction)
    {
        $actionClass = $fromEventAction ? ComplexEventAction::class : ComplexManualAction::class;
        $setting = $settingClass == DefaultSetting::class
            ? $this->getActionDefaultSetting($actionClass)
            : $this->getActionScopedSetting($actionClass);

        $localizedSetting = $setting->localizedSettings()->firstOrFail();

        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user)->putJson("custom/localized-settings/{$localizedSetting->id}")
            ->assertForbidden();
    }

    #[DataProvider('providerLocalizedSetting')]
    public function test_delete_action_localized_settings_success($settingClass, $fromEventAction)
    {
        $actionClass = $fromEventAction ? ComplexEventAction::class : ComplexManualAction::class;
        $setting = $settingClass == DefaultSetting::class
            ? $this->getActionDefaultSetting($actionClass)
            : $this->getActionScopedSetting($actionClass);

        $localizedSetting = $setting->localizedSettings()->firstOrFail();

        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();
        $this->actingAs($user)->delete("custom/localized-settings/$localizedSetting->id")
            ->assertNoContent();

        $this->assertEquals(0, $setting->localizedSettings()->count());
    }

    #[DataProvider('providerLocalizedSetting')]
    public function test_delete_action_localized_settings_forbidden($settingClass, $fromEventAction)
    {
        $actionClass = $fromEventAction ? ComplexEventAction::class : ComplexManualAction::class;
        $setting = $settingClass == DefaultSetting::class
            ? $this->getActionDefaultSetting($actionClass)
            : $this->getActionScopedSetting($actionClass);

        $localizedSetting = $setting->localizedSettings()->firstOrFail();

        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user)->delete("custom/localized-settings/$localizedSetting->id")
            ->assertForbidden();
    }

    public static function providerLocalizedSetting()
    {
        return [
            [DefaultSetting::class, true],
            [DefaultSetting::class, false],
            [ScopedSetting::class, true],
            [ScopedSetting::class, false],
        ];
    }
}
