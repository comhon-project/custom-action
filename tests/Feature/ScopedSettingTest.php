<?php

namespace Tests\Feature;

use App\Models\User;
use Comhon\CustomAction\Models\Action;
use Comhon\CustomAction\Models\DefaultSetting;
use Comhon\CustomAction\Models\EventAction;
use Comhon\CustomAction\Models\ManualAction;
use Comhon\CustomAction\Models\ScopedSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\SetUpWithModelRegistrationTrait;
use Tests\TestCase;

class ScopedSettingTest extends TestCase
{
    use RefreshDatabase;
    use SetUpWithModelRegistrationTrait;

    #[DataProvider('providerBoolean')]
    public function test_store_action_scoped_settings_success($fromEventAction)
    {
        $prefixAction = $fromEventAction ? 'event-actions' : 'manual-actions';
        $actionClass = $fromEventAction ? EventAction::class : ManualAction::class;
        $uniqueProperty = $fromEventAction ? 'id' : 'type';

        /** @var Action $action */
        $action = $actionClass::factory()->create();
        $settingsScope1 = [
            'recipients' => ['to' => ['static' => ['mailables' => [
                ['recipient_id' => User::factory()->create()->id, 'recipient_type' => 'user'],
            ]]]],
            ...($fromEventAction ? [] : ['test' => 'foo']),
        ];
        $scope1 = ['company.name' => 'my company scope 1'];
        $settingsScope2 = [
            'recipients' => ['to' => ['static' => ['mailables' => [
                ['recipient_id' => User::factory()->create()->id, 'recipient_type' => 'user'],
            ]]]],
            ...($fromEventAction ? [] : ['test' => 'bar']),
        ];
        $scope2 = ['company.name' => 'my company scope 2'];
        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();

        // add scope 1
        $response = $this->actingAs($user)->postJson("custom/$prefixAction/{$action->$uniqueProperty}/scoped-settings", [
            'scope' => $scope1,
            'settings' => $settingsScope1,
            'name' => 'Scoped Settings 1',
        ]);
        $response->assertCreated();
        $this->assertEquals(1, $action->scopedSettings()->count());
        $scopedSettings1 = $action->scopedSettings()->where('name', 'Scoped Settings 1')->first();
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
        $response = $this->actingAs($user)->postJson("custom/$prefixAction/{$action->$uniqueProperty}/scoped-settings", [
            'scope' => $scope2,
            'settings' => $settingsScope2,
            'name' => 'Scoped Settings 2',
        ]);
        $response->assertCreated();
        $this->assertEquals(2, $action->scopedSettings()->count());
        $scopedSettings2 = $action->scopedSettings()->where('name', 'Scoped Settings 2')->first();
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
        $response = $this->actingAs($user)->getJson("custom/$prefixAction/{$action->$uniqueProperty}/scoped-settings");
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
        ])->assertJsonMissingPath('data.0.settings');

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
        /** @var ManualAction $action */
        $action = ManualAction::factory()->create();
        $scopedSettings = ScopedSetting::factory([
            'name' => 'my one',
        ])->for($action, 'action')->create();
        ScopedSetting::factory([
            'name' => 'my two',
        ])->for($action, 'action')->create();

        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();
        $params = http_build_query(['name' => 'one']);
        $this->actingAs($user)->getJson("custom/manual-actions/{$action->type}/scoped-settings?$params")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJson([
                'data' => [
                    [
                        'id' => $scopedSettings->id,
                        'name' => 'my one',
                    ],
                ],
            ])->assertJsonMissingPath('data.0.default_setting');
    }

    public function test_store_action_scoped_with_event_context_settings()
    {
        /** @var EventAction $action */
        $action = EventAction::factory()->create();
        $settingsScope1 = [
            'recipients' => ['to' => ['context' => ['mailables' => ['user']]]],
        ];
        $scope1 = ['company.name' => 'my company scope 1'];
        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();

        // add scope
        $response = $this->actingAs($user)->postJson("custom/event-actions/{$action->id}/scoped-settings", [
            'scope' => $scope1,
            'settings' => $settingsScope1,
            'name' => 'my scoped stettings name',
        ]);
        $response->assertCreated();
        $this->assertEquals(1, $action->scopedSettings()->count());
        $scopedSettings1 = $action->scopedSettings()->where('scope', 'like', '%my company scope 1%')->first();
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

    public function test_store_action_scoped_settings_forbidden()
    {
        $action = ManualAction::factory()->create();

        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user)->postJson("custom/manual-actions/{$action->type}/scoped-settings")
            ->assertForbidden();
    }

    public function test_update_action_scoped_settings_success()
    {
        /** @var EventAction $action */
        $action = EventAction::factory()->create();
        $scopedSettings = ScopedSetting::factory([
            'settings' => [
                'recipients' => ['to' => ['static' => ['mailables' => [
                    ['recipient_id' => 789, 'recipient_type' => 'user'],
                ]]]],
            ],
            'scope' => ['company.name' => 'my company scope 1'],
        ])->for($action, 'action')
            ->create();

        $updatedSettings = [
            'recipients' => ['to' => ['static' => ['mailables' => [
                ['recipient_id' => User::factory()->create()->id, 'recipient_type' => 'user'],
            ]]]],
        ];
        $updatedScope = ['company.name' => 'my company scope 2'];
        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();
        $this->actingAs($user)->putJson("custom/scoped-settings/{$scopedSettings->id}", [
            'settings' => $updatedSettings,
            'scope' => $updatedScope,
            'name' => 'updated name',
        ])->assertOk()
            ->assertJson([
                'data' => [
                    'id' => $scopedSettings->id,
                    'scope' => $updatedScope,
                    'settings' => $updatedSettings,
                    'name' => 'updated name',
                ],
            ]);

        $storedScopedSettings = ScopedSetting::findOrFail($scopedSettings->id);
        $this->assertEquals($updatedSettings, $storedScopedSettings->settings);
        $this->assertEquals($updatedScope, $storedScopedSettings->scope);
        $this->assertEquals(1, $action->scopedSettings()->count());
        $this->assertEquals(1, ScopedSetting::count());
    }

    public function test_update_action_scoped_with_event_context_settings()
    {
        /** @var EventAction $action */
        $action = EventAction::factory()
            ->sendMailRegistrationCompany()
            ->create();

        $scopedSettings = ScopedSetting::factory([
            'settings' => [
                'recipients' => ['to' => ['static' => ['mailables' => [
                    ['recipient_id' => 789, 'recipient_type' => 'user'],
                ]]]],
            ],
            'scope' => ['company.name' => 'my company scope 1'],
        ])->for($action, 'action')
            ->create();

        $settingsScope1 = [
            'recipients' => ['to' => ['context' => [
                'mailables' => ['user'],
                'emails' => ['responsibles.*.email'],
            ]]],
        ];
        $scope1 = ['company.name' => 'my company scope 1'];
        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();

        // add scope
        $response = $this->actingAs($user)->putJson("custom/scoped-settings/{$scopedSettings->id}", [
            'scope' => $scope1,
            'settings' => $settingsScope1,
            'name' => 'updated name',
        ]);
        $response->assertOk();
        $this->assertEquals(1, $action->scopedSettings()->count());
        $scopedSettings1 = $action->scopedSettings()->where('scope', 'like', '%my company scope 1%')->first();
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

    #[DataProvider('providerBoolean')]
    public function test_update_action_scoped_settings_forbidden($fromEventAction)
    {
        $withAction = $fromEventAction ? 'withEventAction' : 'withManualAction';

        $scopedSettings = ScopedSetting::factory()->{$withAction}('send-automatic-email')->create();

        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user)->putJson("custom/scoped-settings/{$scopedSettings->id}")
            ->assertForbidden();
    }

    #[DataProvider('providerBoolean')]
    public function test_delete_action_scoped_settings($fromEventAction)
    {
        $actionClass = $fromEventAction ? EventAction::class : ManualAction::class;

        /** @var Action $action */
        $action = $actionClass::factory()->create();

        $scopedSettings = ScopedSetting::factory([
            'settings' => [],
            'scope' => [],
        ])->for($action, 'action')
            ->create();

        $this->assertEquals(1, $action->scopedSettings()->count());
        $this->assertEquals(1, ScopedSetting::count());

        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();
        $response = $this->actingAs($user)->delete("custom/scoped-settings/$scopedSettings->id");
        $response->assertNoContent();
        $this->assertEquals(0, $action->scopedSettings()->count());
        $this->assertEquals(0, ScopedSetting::count());
    }

    #[DataProvider('providerBoolean')]
    public function test_delete_action_scoped_settings_forbidden($fromEventAction)
    {
        $withAction = $fromEventAction ? 'withEventAction' : 'withManualAction';

        /** @var DefaultSetting $defaultSetting */
        $scopedSettings = ScopedSetting::factory()->{$withAction}('send-automatic-email')->create();

        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user)->delete("custom/scoped-settings/$scopedSettings->id")
            ->assertForbidden();
    }

    #[DataProvider('providerLocalizedSettingValidationSendEmail')]
    public function test_action_localized_settings_validation_send_email($settings, $success)
    {
        $defaultSetting = DefaultSetting::factory([
            'settings' => [],
        ])->withEventAction('send-automatic-email')->create();

        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();
        $response = $this->actingAs($user)->postJson("custom/default-settings/{$defaultSetting->id}/localized-settings", [
            'locale' => 'en',
            'settings' => $settings,
        ]);
        $response->assertStatus($success ? 201 : 422);
    }

    public static function providerLocalizedSettingValidationSendEmail()
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
