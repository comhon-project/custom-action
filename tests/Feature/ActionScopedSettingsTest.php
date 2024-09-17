<?php

namespace Tests\Feature;

use App\Models\User;
use Comhon\CustomAction\Models\ActionScopedSettings;
use Comhon\CustomAction\Models\ActionSettings;
use Comhon\CustomAction\Models\EventAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\SetUpWithModelRegistrationTrait;
use Tests\TestCase;

class ActionScopedSettingsTest extends TestCase
{
    use RefreshDatabase;
    use SetUpWithModelRegistrationTrait;

    /**
     * @dataProvider providerActionScopedSettings
     */
    public function testStoreActionScopedSettings($fromEventAction)
    {
        $withAction = $fromEventAction ? 'withEventAction' : 'withManualAction';

        /** @var ActionSettings $actionSettings */
        $actionSettings = ActionSettings::factory([
            'settings' => [],
        ])->{$withAction}('send-email')->create();
        $settingsScope1 = [
            'to_receivers' => [
                ['receiver_id' => User::factory()->create()->id, 'receiver_type' => 'user'],
            ],
        ];
        $scope1 = ['company' => [
            'name' => 'my company scope 1',
        ]];
        $settingsScope2 = [
            'to_receivers' => [
                ['receiver_id' => User::factory()->create()->id, 'receiver_type' => 'user'],
            ],
        ];
        $scope2 = ['company' => [
            'name' => 'my company scope 2',
        ]];
        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();

        // add scope 1
        $response = $this->actingAs($user)->postJson("custom/action-settings/{$actionSettings->id}/scoped-settings", [
            'scope' => $scope1,
            'settings' => $settingsScope1,
            'name' => 'Scoped Settings 1',
        ]);
        $response->assertCreated();
        $this->assertEquals(1, $actionSettings->scopedSettings()->count());
        $scopedSettings1 = $actionSettings->scopedSettings()->where('name', 'Scoped Settings 1')->first();
        $response->assertJson([
            'data' => [
                'id' => $scopedSettings1->id,
                'scope' => $scope1,
                'settings' => $settingsScope1,
                'name' => 'Scoped Settings 1',
            ],
        ]);
        $this->assertEquals($settingsScope1, $scopedSettings1->settings);

        // add scope 2
        $response = $this->actingAs($user)->postJson("custom/action-settings/{$actionSettings->id}/scoped-settings", [
            'scope' => $scope2,
            'settings' => $settingsScope2,
            'name' => 'Scoped Settings 2',
        ]);
        $response->assertCreated();
        $this->assertEquals(2, $actionSettings->scopedSettings()->count());
        $scopedSettings2 = $actionSettings->scopedSettings()->where('name', 'Scoped Settings 2')->first();
        $response->assertJson([
            'data' => [
                'id' => $scopedSettings2->id,
                'scope' => $scope2,
                'settings' => $settingsScope2,
                'name' => 'Scoped Settings 2',
            ],
        ]);
        $this->assertEquals($settingsScope2, $scopedSettings2->settings);

        // get all
        $response = $this->actingAs($user)->getJson("custom/action-settings/{$actionSettings->id}/scoped-settings");
        $response->assertJson([
            'data' => [
                [
                    'id' => $scopedSettings1->id,
                    'name' => 'Scoped Settings 1',
                ],
                [
                    'id' => $scopedSettings2->id,
                    'name' => 'Scoped Settings 2',
                ],
            ],
        ]);

        // get scope 1
        $response = $this->actingAs($user)->getJson("custom/scoped-settings/{$scopedSettings1->id}");
        $response->assertOk();
        $response->assertJson([
            'data' => [
                'id' => $scopedSettings1->id,
                'scope' => $scope1,
                'settings' => $settingsScope1,
                'name' => 'Scoped Settings 1',
            ],
        ]);
    }

    public function testListScopedActionsWithFilter()
    {
        /** @var ActionSettings $actionSettings */
        $actionSettings = ActionSettings::factory()->withManualAction()->create();
        $scopedSettings = ActionScopedSettings::factory([
            'name' => 'my one',
        ])->for($actionSettings, 'actionSettings')->create();
        ActionScopedSettings::factory([
            'name' => 'my two',
        ])->for($actionSettings, 'actionSettings')->create();

        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();
        $params = http_build_query(['name' => 'one']);
        $this->actingAs($user)->getJson("custom/action-settings/$actionSettings->id/scoped-settings?$params")
            ->assertJsonCount(1, 'data')
            ->assertJson([
                'data' => [
                    [
                        'id' => $scopedSettings->id,
                        'name' => 'my one',
                    ],
                ],
            ]);
    }

    public function testStoreActionScopedWithEventContextSettings()
    {
        /** @var ActionSettings $actionSettings */
        $actionSettings = ActionSettings::factory([
            'settings' => [],
        ])->withEventAction('send-email')->create();
        $settingsScope1 = [
            'to_bindings_receivers' => ['user'],
        ];
        $scope1 = ['company' => [
            'name' => 'my company scope 1',
        ]];
        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();

        // add scope
        $response = $this->actingAs($user)->postJson("custom/action-settings/{$actionSettings->id}/scoped-settings", [
            'scope' => $scope1,
            'settings' => $settingsScope1,
            'name' => 'my scoped stettings name',
        ]);
        $response->assertCreated();
        $this->assertEquals(1, $actionSettings->scopedSettings()->count());
        $scopedSettings1 = $actionSettings->scopedSettings()->where('scope', 'like', '%my company scope 1%')->first();
        $response->assertJson([
            'data' => [
                'id' => $scopedSettings1->id,
                'scope' => $scope1,
                'settings' => $settingsScope1,
                'name' => 'my scoped stettings name',
            ],
        ]);
        $this->assertEquals($settingsScope1, $scopedSettings1->settings);
    }

    /**
     * @dataProvider providerActionScopedSettings
     */
    public function testStoreActionScopedSettingsForbidden($fromEventAction)
    {
        $withAction = $fromEventAction ? 'withEventAction' : 'withManualAction';

        /** @var ActionSettings $actionSettings */
        $actionSettings = ActionSettings::factory()->{$withAction}('send-email')->create();

        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user)->postJson("custom/action-settings/{$actionSettings->id}/scoped-settings")
            ->assertForbidden();
    }

    /**
     * @dataProvider providerActionScopedSettings
     */
    public function testUpdateActionScopedSettingsSuccess($fromEventAction)
    {
        $withAction = $fromEventAction ? 'withEventAction' : 'withManualAction';

        /** @var ActionSettings $actionSettings */
        $actionSettings = ActionSettings::factory()->{$withAction}('send-email')->create();
        $scopedSettings = ActionScopedSettings::factory([
            'settings' => [
                'to_receivers' => [
                    ['receiver_id' => 789, 'receiver_type' => 'user'],
                ],
            ],
            'scope' => [
                'company' => [
                    'name' => 'my company scope 1',
                ],
            ],
        ])->for($actionSettings, 'actionSettings')
            ->create();

        $updatedSettings = [
            'to_receivers' => [
                ['receiver_id' => User::factory()->create()->id, 'receiver_type' => 'user'],
            ],
        ];
        $updatedScope = ['company' => [
            'name' => 'my company scope 2',
        ]];
        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();
        $response = $this->actingAs($user)->putJson("custom/scoped-settings/{$scopedSettings->id}", [
            'settings' => $updatedSettings,
            'scope' => $updatedScope,
            'name' => 'updated name',
        ]);
        $response->assertOk();
        $response->assertJson([
            'data' => [
                'id' => $scopedSettings->id,
                'scope' => $updatedScope,
                'settings' => $updatedSettings,
                'name' => 'updated name',
            ],
        ]);
        $storedScopedSettings = ActionScopedSettings::findOrFail($scopedSettings->id);
        $this->assertEquals($updatedSettings, $storedScopedSettings->settings);
        $this->assertEquals($updatedScope, $storedScopedSettings->scope);
        $this->assertEquals(1, $actionSettings->scopedSettings()->count());
        $this->assertEquals(1, ActionScopedSettings::count());
    }

    public function testUpdateActionScopedWithEventContextSettings()
    {
        /** @var ActionSettings $actionSettings */
        $actionSettings = EventAction::factory()
            ->sendMailRegistrationCompany()
            ->create()
            ->actionSettings;

        $scopedSettings = ActionScopedSettings::factory([
            'settings' => [
                'to_receivers' => [
                    ['receiver_id' => 789, 'receiver_type' => 'user'],
                ],
            ],
            'scope' => [
                'company' => [
                    'name' => 'my company scope 1',
                ],
            ],
        ])->for($actionSettings, 'actionSettings')
            ->create();

        $settingsScope1 = [
            'to_bindings_receivers' => ['user'],
            'to_bindings_emails' => ['responsibles.*.email'],
        ];
        $scope1 = ['company' => [
            'name' => 'my company scope 1',
        ]];
        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();

        // add scope
        $response = $this->actingAs($user)->putJson("custom/scoped-settings/{$scopedSettings->id}", [
            'scope' => $scope1,
            'settings' => $settingsScope1,
            'name' => 'updated name',
        ]);
        $response->assertOk();
        $this->assertEquals(1, $actionSettings->scopedSettings()->count());
        $scopedSettings1 = $actionSettings->scopedSettings()->where('scope', 'like', '%my company scope 1%')->first();
        $response->assertJson([
            'data' => [
                'id' => $scopedSettings1->id,
                'scope' => $scope1,
                'settings' => $settingsScope1,
                'name' => 'updated name',
            ],
        ]);
        $this->assertEquals($settingsScope1, $scopedSettings1->settings);
    }

    /**
     * @dataProvider providerActionScopedSettings
     */
    public function testUpdateActionScopedSettingsForbidden($fromEventAction)
    {
        $withAction = $fromEventAction ? 'withEventAction' : 'withManualAction';

        $scopedSettings = ActionScopedSettings::factory()->{$withAction}('send-email')->create();

        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user)->putJson("custom/scoped-settings/{$scopedSettings->id}")
            ->assertForbidden();
    }

    /**
     * @dataProvider providerActionScopedSettings
     */
    public function testDeleteActionScopedSettings($fromEventAction)
    {
        $withAction = $fromEventAction ? 'withEventAction' : 'withManualAction';

        /** @var ActionSettings $actionSettings */
        $actionSettings = ActionSettings::factory()->{$withAction}('send-email')->create();

        $scopedSettings = ActionScopedSettings::factory([
            'settings' => [],
            'scope' => [],
        ])->for($actionSettings, 'actionSettings')
            ->create();

        $this->assertEquals(1, $actionSettings->scopedSettings()->count());
        $this->assertEquals(1, ActionScopedSettings::count());

        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();
        $response = $this->actingAs($user)->delete("custom/scoped-settings/$scopedSettings->id");
        $response->assertNoContent();
        $this->assertEquals(0, $actionSettings->scopedSettings()->count());
        $this->assertEquals(0, ActionScopedSettings::count());
    }

    /**
     * @dataProvider providerActionScopedSettings
     */
    public function testDeleteActionScopedSettingsForbidden($fromEventAction)
    {
        $withAction = $fromEventAction ? 'withEventAction' : 'withManualAction';

        /** @var ActionSettings $actionSettings */
        $scopedSettings = ActionScopedSettings::factory()->{$withAction}('send-email')->create();

        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user)->delete("custom/scoped-settings/$scopedSettings->id")
            ->assertForbidden();
    }

    public static function providerActionScopedSettings()
    {
        return [
            [true],
            [false],
        ];
    }

    /**
     * @dataProvider providerActionLocalizedSettingsValidationSendEmail
     */
    public function testActionLocalizedSettingsValidationSendEmail($settings, $success)
    {
        $actionSettings = ActionSettings::factory([
            'settings' => [],
        ])->withEventAction('send-email')->create();

        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();
        $response = $this->actingAs($user)->postJson("custom/action-settings/{$actionSettings->id}/localized-settings", [
            'locale' => 'en',
            'settings' => $settings,
        ]);
        $response->assertStatus($success ? 201 : 422);
    }

    public static function providerActionLocalizedSettingsValidationSendEmail()
    {
        return [
            [
                [
                    'subject' => 'original {{ to.name ? "true" : "false" }} subject',
                    'body' => 'original {{ to.name ? "true" : "false" }} body {{ to.name ? "true" : "false" }}',
                ],
                true,
            ],
            [
                [
                    'subject' => 'original {{ "true }} subject',
                    'body' => 'original subject',
                ],
                false,
            ],
            [
                [
                    'subject' => 'original subject',
                    'body' => 'original {{ "true }} subject',
                ],
                false,
            ],
        ];
    }
}
