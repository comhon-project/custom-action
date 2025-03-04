<?php

namespace Tests\Feature;

use App\Models\User;
use Comhon\CustomAction\Models\Action;
use Comhon\CustomAction\Models\DefaultSetting;
use Comhon\CustomAction\Models\EventAction;
use Comhon\CustomAction\Models\ManualAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\SetUpWithModelRegistrationTrait;
use Tests\TestCase;

class DefaultSettingTest extends TestCase
{
    use RefreshDatabase;
    use SetUpWithModelRegistrationTrait;

    public function test_get_action_settings_success()
    {
        $defaultSetting = DefaultSetting::factory([
            'settings' => [
                'subject' => 'the subject',
            ],
        ])->withManualAction()->create();

        /** @var User $consumer */
        $consumer = User::factory()->hasConsumerAbility()->create();
        $this->actingAs($consumer)->getJson("custom/default-settings/{$defaultSetting->id}")
            ->assertOk()
            ->assertJson([
                'data' => [
                    'id' => $defaultSetting->id,
                    'settings' => [
                        'subject' => 'the subject',
                    ],
                ],
            ]);
    }

    public function test_get_action_settings_forbidden()
    {
        $defaultSetting = DefaultSetting::factory()->withManualAction()->create();
        /** @var User $consumer */
        $consumer = User::factory()->create();
        $this->actingAs($consumer)->getJson("custom/default-settings/{$defaultSetting->id}")
            ->assertForbidden();
    }

    public function test_update_generic_action_settings()
    {
        $defaultSetting = DefaultSetting::factory([
            'settings' => [],
        ])->withEventAction('send-automatic-email')->create();
        $newSettings = [
            'recipients' => ['to' => ['static' => ['mailables' => [
                ['recipient_id' => User::factory()->create()->id, 'recipient_type' => 'user'],
            ]]]],
        ];

        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();
        $this->actingAs($user)->putJson("custom/default-settings/$defaultSetting->id", [
            'settings' => $newSettings,
        ])->assertOk()
            ->assertJson([
                'data' => [
                    'id' => $defaultSetting->id,
                    'settings' => $newSettings,
                ],
            ]);

        $this->assertEquals($newSettings, DefaultSetting::findOrFail($defaultSetting->id)->settings);
    }

    public function test_update_manual_action_settings()
    {
        $defaultSetting = ManualAction::factory([
            'type' => 'send-manual-company-email',
        ])->sendMailRegistrationCompany()
            ->create()
            ->defaultSetting;

        $newSettings = [
            'recipients' => ['to' => ['static' => ['mailables' => [
                ['recipient_id' => User::factory()->create()->id, 'recipient_type' => 'user'],
            ]]]],
            'test' => 'foo',
        ];

        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();
        $this->actingAs($user)->putJson("custom/default-settings/$defaultSetting->id", [
            'settings' => $newSettings,
        ])->assertOk()
            ->assertJson([
                'data' => [
                    'id' => $defaultSetting->id,
                    'settings' => $newSettings,
                ],
            ]);

        $this->assertEquals($newSettings, DefaultSetting::findOrFail($defaultSetting->id)->settings);
    }

    public function test_update_manual_action_settings_missing_required()
    {
        $defaultSetting = ManualAction::factory([
            'type' => 'send-manual-company-email',
        ])->sendMailRegistrationCompany()
            ->create()
            ->defaultSetting;

        $newSettings = [
            'recipients' => ['to' => ['static' => ['mailables' => [
                ['recipient_id' => User::factory()->create()->id, 'recipient_type' => 'user'],
            ]]]],
        ];

        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();
        $this->actingAs($user)->putJson("custom/default-settings/$defaultSetting->id", [
            'settings' => $newSettings,
        ])->assertUnprocessable()
            ->assertJson([
                'message' => 'The settings.test field is required.',
                'errors' => [
                    'settings.test' => [
                        'The settings.test field is required.',
                    ],
                ],
            ]);

    }

    public function test_update_action_with_event_context_settings()
    {
        $defaultSetting = DefaultSetting::factory([
            'settings' => [],
        ])->withEventAction('send-automatic-email')->create();
        $newSettings = [
            'recipients' => ['to' => ['bindings' => ['mailables' => ['user']]]],
        ];

        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();
        $this->actingAs($user)->putJson("custom/default-settings/$defaultSetting->id", [
            'settings' => $newSettings,
        ])->assertOk()
            ->assertJson([
                'data' => [
                    'id' => $defaultSetting->id,
                    'settings' => $newSettings,
                ],
            ]);

        $this->assertEquals($newSettings, DefaultSetting::findOrFail($defaultSetting->id)->settings);
    }

    public function test_update_action_settings_forbidden()
    {
        $defaultSetting = DefaultSetting::factory([
            'settings' => [],
        ])->withEventAction('send-automatic-email')->create();

        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user)->putJson("custom/default-settings/$defaultSetting->id")
            ->assertForbidden();
    }

    #[DataProvider('providerBoolean')]
    public function test_store_action_settings_success($fromEventAction)
    {
        $prefixAction = $fromEventAction ? 'event-actions' : 'manual-actions';
        $actionClass = $fromEventAction ? EventAction::class : ManualAction::class;

        /** @var Action $action */
        $action = $actionClass::factory()->create();
        $input = [
            'recipients' => ['to' => ['static' => ['mailables' => [
                ['recipient_id' => User::factory()->create()->id, 'recipient_type' => 'user'],
            ]]]],
            ...($fromEventAction ? [] : ['test' => 'foo']),
        ];
        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();

        $response = $this->actingAs($user)->postJson("custom/$prefixAction/{$action->getKey()}/default-settings", [
            'settings' => $input,
        ]);
        $response->assertCreated();
        $this->assertEquals(1, $action->defaultSetting()->count());
        $defaultSetting = $action->defaultSetting()->first();
        $response->assertJson([
            'data' => [
                'id' => $defaultSetting->id,
                'settings' => $input,
            ],
        ]);
        $this->assertEquals($input, $defaultSetting->settings);

        $this->actingAs($user)->postJson("custom/$prefixAction/{$action->getKey()}/default-settings", [
            'settings' => $input,
        ])->assertForbidden()
            ->assertJson([
                'message' => 'default settings already exist',
            ]);
    }

    public function test_store_action_scoped_settings_forbidden()
    {
        $action = ManualAction::factory()->create();

        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user)->postJson("custom/manual-actions/{$action->type}/scoped-settings")
            ->assertForbidden();
    }
}
