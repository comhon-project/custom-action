<?php

namespace Tests\Feature;

use App\Models\User;
use Comhon\CustomAction\Models\DefaultSetting;
use Comhon\CustomAction\Models\LocalizedSetting;
use Comhon\CustomAction\Models\ScopedSetting;
use Comhon\CustomAction\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\SetUpWithModelRegistrationTrait;
use Tests\TestCase;

class LocalizedSettingTest extends TestCase
{
    use RefreshDatabase;
    use SetUpWithModelRegistrationTrait;

    #[DataProvider('providerLocalizedSetting')]
    public function test_store_action_localized_settings($settingClass, $fromEventAction)
    {
        $resource = $settingClass == DefaultSetting::class ? 'default-settings' : 'scoped-settings';
        $withAction = $fromEventAction ? 'withEventAction' : 'withManualAction';

        /** @var Setting $setting */
        $setting = $settingClass::factory([
            'settings' => [],
        ])->{$withAction}('send-email')->create();
        $originalSettingsEn = [
            'subject' => 'original subject',
            'body' => 'original body',
        ];
        $originalSettingsFr = [
            'subject' => 'sujet original',
            'body' => 'corps original',
        ];
        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();

        // add en
        $response = $this->actingAs($user)->postJson("custom/{$resource}/{$setting->id}/localized-settings", [
            'locale' => 'en',
            'settings' => $originalSettingsEn,
        ]);
        $response->assertCreated();
        $this->assertEquals(1, $setting->localizedSettings()->count());
        $localizedSettingEn = $setting->localizedSettings()->where('locale', 'en')->first();
        $response->assertJson([
            'data' => [
                'id' => $localizedSettingEn->id,
                'locale' => 'en',
                'settings' => $originalSettingsEn,
            ],
        ]);
        $this->assertEquals($originalSettingsEn, $localizedSettingEn->settings);

        // add fr
        $response = $this->actingAs($user)->postJson("custom/{$resource}/{$setting->id}/localized-settings", [
            'locale' => 'fr',
            'settings' => $originalSettingsFr,
        ]);
        $this->assertEquals(2, $setting->localizedSettings()->count());
        $localizedSettingFr = $setting->localizedSettings()->where('locale', 'fr')->first();
        $response->assertJson([
            'data' => [
                'id' => $localizedSettingFr->id,
                'locale' => 'fr',
                'settings' => $originalSettingsFr,
            ],
        ]);
        $this->assertEquals($originalSettingsFr, $localizedSettingFr->settings);

        // get all
        $response = $this->actingAs($user)->getJson("custom/{$resource}/{$setting->id}/localized-settings");
        $response->assertJson([
            'data' => [
                ['id' => $localizedSettingEn->id, 'locale' => 'en'],
                ['id' => $localizedSettingFr->id, 'locale' => 'fr'],
            ],
        ]);

        // get en
        $response = $this->actingAs($user)->getJson("custom/localized-settings/{$localizedSettingEn->id}");
        $response->assertJson([
            'data' => [
                'id' => $localizedSettingEn->id,
                'locale' => 'en',
                'settings' => $originalSettingsEn,
            ],
        ]);
    }

    #[DataProvider('providerLocalizedSetting')]
    public function test_store_action_localized_settings_missing_required($settingClass, $fromEventAction)
    {
        $resource = $settingClass == DefaultSetting::class ? 'default-settings' : 'scoped-settings';
        $withAction = $fromEventAction ? 'withEventAction' : 'withManualAction';

        /** @var Setting $setting */
        $setting = $settingClass::factory([
            'settings' => [],
        ])->{$withAction}('send-email')->create();
        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();

        $this->actingAs($user)->postJson("custom/{$resource}/{$setting->id}/localized-settings", [
            'locale' => 'en',
            'settings' => ['foo' => 'bar'],
        ])->assertUnprocessable()
            ->assertJson([
                'message' => 'The settings.subject field is required. (and 1 more error)',
                'errors' => [
                    'settings.subject' => [
                        'The settings.subject field is required.',
                    ],
                    'settings.body' => [
                        'The settings.body field is required.',
                    ],
                ],
            ]);
    }

    #[DataProvider('providerLocalizedSetting')]
    public function test_store_action_localized_settings_with_localized_settings($settingClass, $fromEventAction)
    {
        $resource = $settingClass == DefaultSetting::class ? 'default-settings' : 'scoped-settings';
        $withAction = $fromEventAction ? 'withEventAction' : 'withManualAction';
        $typeAction = $fromEventAction ? 'send-automatic-company-email' : 'send-manual-company-email';

        /** @var Setting $setting */
        $setting = $settingClass::factory()
            ->{$withAction}($typeAction)
            ->create();
        $originalSettingsEn = [
            'subject' => 'original subject',
            'body' => 'original body',
            'test_localized' => 'foo',
        ];
        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();

        // add en
        $response = $this->actingAs($user)->postJson("custom/{$resource}/{$setting->id}/localized-settings", [
            'locale' => 'en',
            'settings' => $originalSettingsEn,
        ]);
        $response->assertCreated();
        $this->assertEquals(1, $setting->localizedSettings()->count());
        $localizedSettingEn = $setting->localizedSettings()->where('locale', 'en')->first();
        $response->assertJson([
            'data' => [
                'id' => $localizedSettingEn->id,
                'locale' => 'en',
                'settings' => $originalSettingsEn,
            ],
        ]);
        $this->assertEquals($originalSettingsEn, $localizedSettingEn->settings);
    }

    #[DataProvider('providerLocalizedSetting')]
    public function test_store_action_localized_settings_forbidden($settingClass, $fromEventAction)
    {
        $resource = $settingClass == DefaultSetting::class ? 'default-settings' : 'scoped-settings';
        $withAction = $fromEventAction ? 'withEventAction' : 'withManualAction';
        $typeAction = $fromEventAction ? 'send-automatic-company-email' : 'send-manual-company-email';

        /** @var Setting $setting */
        $setting = $settingClass::factory()
            ->{$withAction}($typeAction)->create();

        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user)->postJson("custom/{$resource}/{$setting->id}/localized-settings")
            ->assertForbidden();
    }

    #[DataProvider('providerLocalizedSetting')]
    public function test_update_action_localized_settings($settingClass, $fromEventAction)
    {
        $withAction = $fromEventAction ? 'withEventAction' : 'withManualAction';

        /** @var Setting $setting */
        $setting = $settingClass::factory()
            ->{$withAction}('send-email')
            ->create();

        $localizedSetting = new LocalizedSetting;
        $localizedSetting->settings = [
            'subject' => 'original subject',
            'body' => 'original body',
        ];
        $localizedSetting->locale = 'en';
        $localizedSetting->localizable()->associate($setting);
        $localizedSetting->save();
        $updatedSettings = [
            'subject' => 'updated subject',
            'body' => 'updated body',
        ];
        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();
        $response = $this->actingAs($user)->putJson("custom/localized-settings/{$localizedSetting->id}", [
            'settings' => $updatedSettings,
            'locale' => 'es',
        ]);
        $response->assertOk();
        $response->assertJson([
            'data' => [
                'id' => $localizedSetting->id,
                'locale' => 'es',
                'settings' => $updatedSettings,
            ],
        ]);
        $this->assertEquals($updatedSettings, LocalizedSetting::where('locale', 'es')->firstOrFail()->settings);
        $this->assertEquals(1, $setting->localizedSettings()->count());
        $this->assertEquals(1, LocalizedSetting::count());
    }

    #[DataProvider('providerLocalizedSetting')]
    public function test_update_action_localized_settings_with_action_localized_setting($settingClass, $fromEventAction)
    {
        $withAction = $fromEventAction ? 'withEventAction' : 'withManualAction';
        $typeAction = $fromEventAction ? 'send-automatic-company-email' : 'send-manual-company-email';

        /** @var Setting $setting */
        $setting = $settingClass::factory()
            ->{$withAction}($typeAction)
            ->create();
        $localizedSetting = new LocalizedSetting;
        $localizedSetting->settings = [
            'subject' => 'original subject',
            'body' => 'original body',
            'test_localized' => 'original test_localized',
        ];
        $localizedSetting->locale = 'en';
        $localizedSetting->localizable()->associate($setting);
        $localizedSetting->save();
        $updatedSettings = [
            'subject' => 'updated subject',
            'body' => 'updated body',
            'test_localized' => 'updated test_localized',
        ];
        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();
        $response = $this->actingAs($user)->putJson("custom/localized-settings/{$localizedSetting->id}", [
            'settings' => $updatedSettings,
            'locale' => 'es',
        ]);
        $response->assertOk();
        $response->assertJson([
            'data' => [
                'id' => $localizedSetting->id,
                'locale' => 'es',
                'settings' => $updatedSettings,
            ],
        ]);
        $this->assertEquals($updatedSettings, LocalizedSetting::where('locale', 'es')->firstOrFail()->settings);
        $this->assertEquals(1, $setting->localizedSettings()->count());
        $this->assertEquals(1, LocalizedSetting::count());
    }

    #[DataProvider('providerLocalizedSetting')]
    public function test_update_action_localized_settings_forbidden($settingClass, $fromEventAction)
    {
        $withAction = $fromEventAction ? 'withEventAction' : 'withManualAction';

        /** @var Setting $setting */
        $setting = $settingClass::factory()->{$withAction}('send-email')->create();
        $localizedSetting = new LocalizedSetting;
        $localizedSetting->settings = [
            'subject' => 'original subject',
            'body' => 'original body',
        ];
        $localizedSetting->locale = 'en';
        $localizedSetting->localizable()->associate($setting);
        $localizedSetting->save();

        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user)->putJson("custom/localized-settings/{$localizedSetting->id}")
            ->assertForbidden();
    }

    #[DataProvider('providerLocalizedSetting')]
    public function test_delete_action_localized_settings($settingClass, $fromEventAction)
    {
        $withAction = $fromEventAction ? 'withEventAction' : 'withManualAction';

        /** @var Setting $setting */
        $setting = $settingClass::factory()->{$withAction}('send-email')->create();
        $localizedSetting = new LocalizedSetting;
        $localizedSetting->settings = [];
        $localizedSetting->locale = 'en';
        $localizedSetting->localizable()->associate($setting);
        $localizedSetting->save();

        $this->assertEquals(1, $setting->localizedSettings()->count());
        $this->assertEquals(1, LocalizedSetting::count());

        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();
        $response = $this->actingAs($user)->delete("custom/localized-settings/$localizedSetting->id");
        $response->assertNoContent();
        $this->assertEquals(0, $setting->localizedSettings()->count());
        $this->assertEquals(0, LocalizedSetting::count());
    }

    #[DataProvider('providerLocalizedSetting')]
    public function test_delete_action_localized_settings_forbidden($settingClass, $fromEventAction)
    {
        $withAction = $fromEventAction ? 'withEventAction' : 'withManualAction';

        /** @var Setting $setting */
        $setting = $settingClass::factory()->$withAction('send-email')->create();
        $localizedSetting = new LocalizedSetting;
        $localizedSetting->settings = [];
        $localizedSetting->locale = 'en';
        $localizedSetting->localizable()->associate($setting);
        $localizedSetting->save();

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
