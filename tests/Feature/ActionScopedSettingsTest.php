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
    public function test_store_action_scoped_settings($fromEventAction)
    {
        $withAction = $fromEventAction ? 'withEventAction' : 'withManualAction';

        /** @var ActionSettings $actionSettings */
        $actionSettings = ActionSettings::factory([
            'settings' => [],
        ])->{$withAction}('send-email')->create();
        $settingsScope1 = [
            'recipients' => ['to' => ['static' => ['mailables' => [
                ['recipient_id' => User::factory()->create()->id, 'recipient_type' => 'user'],
            ]]]],
        ];
        $scope1 = ['company' => [
            'name' => 'my company scope 1',
        ]];
        $settingsScope2 = [
            'recipients' => ['to' => ['static' => ['mailables' => [
                ['recipient_id' => User::factory()->create()->id, 'recipient_type' => 'user'],
            ]]]],
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

    public function test_list_scoped_actions_with_filter()
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

    public function test_store_action_scoped_with_event_context_settings()
    {
        /** @var ActionSettings $actionSettings */
        $actionSettings = ActionSettings::factory([
            'settings' => [],
        ])->withEventAction('send-email')->create();
        $settingsScope1 = [
            'recipients' => ['to' => ['bindings' => ['mailables' => ['user']]]],
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
    public function test_store_action_scoped_settings_forbidden($fromEventAction)
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
    public function test_update_action_scoped_settings_success($fromEventAction)
    {
        $withAction = $fromEventAction ? 'withEventAction' : 'withManualAction';

        /** @var ActionSettings $actionSettings */
        $actionSettings = ActionSettings::factory()->{$withAction}('send-email')->create();
        $scopedSettings = ActionScopedSettings::factory([
            'settings' => [
                'recipients' => ['to' => ['static' => ['mailables' => [
                    ['recipient_id' => 789, 'recipient_type' => 'user'],
                ]]]],
            ],
            'scope' => [
                'company' => [
                    'name' => 'my company scope 1',
                ],
            ],
        ])->for($actionSettings, 'actionSettings')
            ->create();

        $updatedSettings = [
            'recipients' => ['to' => ['static' => ['mailables' => [
                ['recipient_id' => User::factory()->create()->id, 'recipient_type' => 'user'],
            ]]]],
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

    public function test_update_action_scoped_with_event_context_settings()
    {
        /** @var ActionSettings $actionSettings */
        $actionSettings = EventAction::factory()
            ->sendMailRegistrationCompany()
            ->create()
            ->actionSettings;

        $scopedSettings = ActionScopedSettings::factory([
            'settings' => [
                'recipients' => ['to' => ['static' => ['mailables' => [
                    ['recipient_id' => 789, 'recipient_type' => 'user'],
                ]]]],
            ],
            'scope' => [
                'company' => [
                    'name' => 'my company scope 1',
                ],
            ],
        ])->for($actionSettings, 'actionSettings')
            ->create();

        $settingsScope1 = [
            'recipients' => ['to' => ['bindings' => [
                'mailables' => ['user'],
                'emails' => ['responsibles.*.email'],
            ]]],
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
    public function test_update_action_scoped_settings_forbidden($fromEventAction)
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
    public function test_delete_action_scoped_settings($fromEventAction)
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
    public function test_delete_action_scoped_settings_forbidden($fromEventAction)
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
    public function test_action_localized_settings_validation_send_email($settings, $success)
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
