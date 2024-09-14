<?php

namespace Tests\Feature;

use App\Models\User;
use Comhon\CustomAction\Models\ActionLocalizedSettings;
use Comhon\CustomAction\Models\ActionScopedSettings;
use Comhon\CustomAction\Models\ActionSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\SetUpWithModelRegistrationTrait;
use Tests\TestCase;

class ActionLocalizedSettingsTest extends TestCase
{
    use RefreshDatabase;
    use SetUpWithModelRegistrationTrait;

    /**
     * @dataProvider providerActionLocalizedSettings
     */
    public function testStoreActionLocalizedSettings($settingsContainerClass, $fromEventAction)
    {
        $resource = $settingsContainerClass == ActionSettings::class ? 'action-settings' : 'scoped-settings';
        $withActionType = $fromEventAction ? 'withEventActionType' : 'withManualActionType';

        /** @var ActionSettingsContainer $settingsContainer */
        $settingsContainer = $settingsContainerClass::factory([
            'settings' => [],
        ])->{$withActionType}('send-email')->create();
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
        $response = $this->actingAs($user)->postJson("custom/{$resource}/{$settingsContainer->id}/localized-settings", [
            'locale' => 'en',
            'settings' => $originalSettingsEn,
        ]);
        $response->assertCreated();
        $this->assertEquals(1, $settingsContainer->localizedSettings()->count());
        $localizedSettingsEn = $settingsContainer->localizedSettings()->where('locale', 'en')->first();
        $response->assertJson([
            'data' => [
                'id' => $localizedSettingsEn->id,
                'locale' => 'en',
                'settings' => $originalSettingsEn,
            ],
        ]);
        $this->assertEquals($originalSettingsEn, $localizedSettingsEn->settings);

        // add fr
        $response = $this->actingAs($user)->postJson("custom/{$resource}/{$settingsContainer->id}/localized-settings", [
            'locale' => 'fr',
            'settings' => $originalSettingsFr,
        ]);
        $this->assertEquals(2, $settingsContainer->localizedSettings()->count());
        $localizedSettingsFr = $settingsContainer->localizedSettings()->where('locale', 'fr')->first();
        $response->assertJson([
            'data' => [
                'id' => $localizedSettingsFr->id,
                'locale' => 'fr',
                'settings' => $originalSettingsFr,
            ],
        ]);
        $this->assertEquals($originalSettingsFr, $localizedSettingsFr->settings);

        // get all
        $response = $this->actingAs($user)->getJson("custom/{$resource}/{$settingsContainer->id}/localized-settings");
        $response->assertJson([
            'data' => [
                ['id' => $localizedSettingsEn->id, 'locale' => 'en'],
                ['id' => $localizedSettingsFr->id, 'locale' => 'fr'],
            ],
        ]);

        // get en
        $response = $this->actingAs($user)->getJson("custom/localized-settings/{$localizedSettingsEn->id}");
        $response->assertJson([
            'data' => [
                'id' => $localizedSettingsEn->id,
                'locale' => 'en',
                'settings' => $originalSettingsEn,
            ],
        ]);
    }

    /**
     * @dataProvider providerActionLocalizedSettings
     */
    public function testStoreActionLocalizedSettingsMissingRequired($settingsContainerClass, $fromEventAction)
    {
        $resource = $settingsContainerClass == ActionSettings::class ? 'action-settings' : 'scoped-settings';
        $withActionType = $fromEventAction ? 'withEventActionType' : 'withManualActionType';

        /** @var ActionSettingsContainer $settingsContainer */
        $settingsContainer = $settingsContainerClass::factory([
            'settings' => [],
        ])->{$withActionType}('send-email')->create();
        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();

        $this->actingAs($user)->postJson("custom/{$resource}/{$settingsContainer->id}/localized-settings", [
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

    /**
     * @dataProvider providerActionLocalizedSettings
     */
    public function testStoreActionLocalizedSettingsWithLocalizedSettings($settingsContainerClass, $fromEventAction)
    {
        $resource = $settingsContainerClass == ActionSettings::class ? 'action-settings' : 'scoped-settings';
        $withActionType = $fromEventAction ? 'withEventActionType' : 'withManualActionType';

        /** @var ActionSettingsContainer $settingsContainer */
        $settingsContainer = $settingsContainerClass::factory()
            ->{$withActionType}('send-company-email')
            ->create();
        $originalSettingsEn = [
            'subject' => 'original subject',
            'body' => 'original body',
            'test_localized' => 'foo',
        ];
        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();

        // add en
        $response = $this->actingAs($user)->postJson("custom/{$resource}/{$settingsContainer->id}/localized-settings", [
            'locale' => 'en',
            'settings' => $originalSettingsEn,
        ]);
        $response->assertCreated();
        $this->assertEquals(1, $settingsContainer->localizedSettings()->count());
        $localizedSettingsEn = $settingsContainer->localizedSettings()->where('locale', 'en')->first();
        $response->assertJson([
            'data' => [
                'id' => $localizedSettingsEn->id,
                'locale' => 'en',
                'settings' => $originalSettingsEn,
            ],
        ]);
        $this->assertEquals($originalSettingsEn, $localizedSettingsEn->settings);
    }

    /**
     * @dataProvider providerActionLocalizedSettings
     */
    public function testStoreActionLocalizedSettingsForbidden($settingsContainerClass, $fromEventAction)
    {
        $resource = $settingsContainerClass == ActionSettings::class ? 'action-settings' : 'scoped-settings';
        $withActionType = $fromEventAction ? 'withEventActionType' : 'withManualActionType';

        /** @var ActionSettingsContainer $settingsContainer */
        $settingsContainer = $settingsContainerClass::factory()
            ->{$withActionType}('send-company-email')->create();

        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user)->postJson("custom/{$resource}/{$settingsContainer->id}/localized-settings")
            ->assertForbidden();
    }

    /**
     * @dataProvider providerActionLocalizedSettings
     */
    public function testUpdateActionLocalizedSettings($settingsContainerClass, $fromEventAction)
    {
        $withActionType = $fromEventAction ? 'withEventActionType' : 'withManualActionType';

        /** @var ActionSettingsContainer $settingsContainer */
        $settingsContainer = $settingsContainerClass::factory()
            ->{$withActionType}('send-email')
            ->create();

        $localizedSettings = new ActionLocalizedSettings;
        $localizedSettings->settings = [
            'subject' => 'original subject',
            'body' => 'original body',
        ];
        $localizedSettings->locale = 'en';
        $localizedSettings->localizable()->associate($settingsContainer);
        $localizedSettings->save();
        $updatedSettings = [
            'subject' => 'updated subject',
            'body' => 'updated body',
        ];
        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();
        $response = $this->actingAs($user)->putJson("custom/localized-settings/{$localizedSettings->id}", [
            'settings' => $updatedSettings,
            'locale' => 'es',
        ]);
        $response->assertOk();
        $response->assertJson([
            'data' => [
                'id' => $localizedSettings->id,
                'locale' => 'es',
                'settings' => $updatedSettings,
            ],
        ]);
        $this->assertEquals($updatedSettings, ActionLocalizedSettings::where('locale', 'es')->firstOrFail()->settings);
        $this->assertEquals(1, $settingsContainer->localizedSettings()->count());
        $this->assertEquals(1, ActionLocalizedSettings::count());
    }

    /**
     * @dataProvider providerActionLocalizedSettings
     */
    public function testUpdateActionLocalizedSettingsWithActionLocalizedSetting($settingsContainerClass, $fromEventAction)
    {
        $withActionType = $fromEventAction ? 'withEventActionType' : 'withManualActionType';

        /** @var ActionSettingsContainer $settingsContainer */
        $settingsContainer = $settingsContainerClass::factory()
            ->{$withActionType}('send-company-email')
            ->create();
        $localizedSettings = new ActionLocalizedSettings;
        $localizedSettings->settings = [
            'subject' => 'original subject',
            'body' => 'original body',
            'test_localized' => 'original test_localized',
        ];
        $localizedSettings->locale = 'en';
        $localizedSettings->localizable()->associate($settingsContainer);
        $localizedSettings->save();
        $updatedSettings = [
            'subject' => 'updated subject',
            'body' => 'updated body',
            'test_localized' => 'updated test_localized',
        ];
        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();
        $response = $this->actingAs($user)->putJson("custom/localized-settings/{$localizedSettings->id}", [
            'settings' => $updatedSettings,
            'locale' => 'es',
        ]);
        $response->assertOk();
        $response->assertJson([
            'data' => [
                'id' => $localizedSettings->id,
                'locale' => 'es',
                'settings' => $updatedSettings,
            ],
        ]);
        $this->assertEquals($updatedSettings, ActionLocalizedSettings::where('locale', 'es')->firstOrFail()->settings);
        $this->assertEquals(1, $settingsContainer->localizedSettings()->count());
        $this->assertEquals(1, ActionLocalizedSettings::count());
    }

    /**
     * @dataProvider providerActionLocalizedSettings
     */
    public function testUpdateActionLocalizedSettingsForbidden($settingsContainerClass, $fromEventAction)
    {
        $withActionType = $fromEventAction ? 'withEventActionType' : 'withManualActionType';

        /** @var ActionSettingsContainer $settingsContainer */
        $settingsContainer = $settingsContainerClass::factory()->{$withActionType}('send-email')->create();
        $localizedSettings = new ActionLocalizedSettings;
        $localizedSettings->settings = [
            'subject' => 'original subject',
            'body' => 'original body',
        ];
        $localizedSettings->locale = 'en';
        $localizedSettings->localizable()->associate($settingsContainer);
        $localizedSettings->save();

        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user)->putJson("custom/localized-settings/{$localizedSettings->id}")
            ->assertForbidden();
    }

    /**
     * @dataProvider providerActionLocalizedSettings
     */
    public function testDeleteActionLocalizedSettings($settingsContainerClass, $fromEventAction)
    {
        $withActionType = $fromEventAction ? 'withEventActionType' : 'withManualActionType';

        /** @var ActionSettingsContainer $settingsContainer */
        $settingsContainer = $settingsContainerClass::factory()->{$withActionType}('send-email')->create();
        $localizedSettings = new ActionLocalizedSettings;
        $localizedSettings->settings = [];
        $localizedSettings->locale = 'en';
        $localizedSettings->localizable()->associate($settingsContainer);
        $localizedSettings->save();

        $this->assertEquals(1, $settingsContainer->localizedSettings()->count());
        $this->assertEquals(1, ActionLocalizedSettings::count());

        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();
        $response = $this->actingAs($user)->delete("custom/localized-settings/$localizedSettings->id");
        $response->assertNoContent();
        $this->assertEquals(0, $settingsContainer->localizedSettings()->count());
        $this->assertEquals(0, ActionLocalizedSettings::count());
    }

    /**
     * @dataProvider providerActionLocalizedSettings
     */
    public function testDeleteActionLocalizedSettingsForbidden($settingsContainerClass, $fromEventAction)
    {
        $withActionType = $fromEventAction ? 'withEventActionType' : 'withManualActionType';

        /** @var ActionSettingsContainer $settingsContainer */
        $settingsContainer = $settingsContainerClass::factory()->$withActionType('send-email')->create();
        $localizedSettings = new ActionLocalizedSettings;
        $localizedSettings->settings = [];
        $localizedSettings->locale = 'en';
        $localizedSettings->localizable()->associate($settingsContainer);
        $localizedSettings->save();

        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user)->delete("custom/localized-settings/$localizedSettings->id")
            ->assertForbidden();
    }

    public static function providerActionLocalizedSettings()
    {
        return [
            [ActionSettings::class, true],
            [ActionSettings::class, false],
            [ActionScopedSettings::class, true],
            [ActionScopedSettings::class, false],
        ];
    }
}
