<?php

namespace Tests\Feature;

use App\Models\User;
use Comhon\CustomAction\Models\ActionSettings;
use Comhon\CustomAction\Models\ManualAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\SetUpWithModelRegistrationTrait;
use Tests\TestCase;

class ActionSettingsTest extends TestCase
{
    use RefreshDatabase;
    use SetUpWithModelRegistrationTrait;

    public function test_get_action_settings_success()
    {
        $actionSettings = ActionSettings::factory([
            'settings' => [
                'subject' => 'the subject',
            ],
        ])->withManualAction()->create();

        /** @var User $consumer */
        $consumer = User::factory()->hasConsumerAbility()->create();
        $this->actingAs($consumer)->getJson("custom/action-settings/{$actionSettings->id}")
            ->assertOk()
            ->assertJson([
                'data' => [
                    'id' => $actionSettings->id,
                    'settings' => [
                        'subject' => 'the subject',
                    ],
                ],
            ]);
    }

    public function test_get_action_settings_forbidden()
    {
        $actionSettings = ActionSettings::factory()->withManualAction()->create();
        /** @var User $consumer */
        $consumer = User::factory()->create();
        $this->actingAs($consumer)->getJson("custom/action-settings/{$actionSettings->id}")
            ->assertForbidden();
    }

    public function test_update_generic_action_settings()
    {
        $actionSettings = ActionSettings::factory([
            'settings' => [],
        ])->withEventAction('send-email')->create();
        $newSettings = [
            'recipients' => ['to' => ['static' => ['mailables' => [
                ['recipient_id' => User::factory()->create()->id, 'recipient_type' => 'user'],
            ]]]],
        ];

        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();
        $this->actingAs($user)->putJson("custom/action-settings/$actionSettings->id", [
            'settings' => $newSettings,
        ])->assertOk()
            ->assertJson([
                'data' => [
                    'id' => $actionSettings->id,
                    'settings' => $newSettings,
                ],
            ]);

        $this->assertEquals($newSettings, ActionSettings::findOrFail($actionSettings->id)->settings);
    }

    public function test_update_manual_action_settings()
    {
        $actionSettings = ManualAction::factory([
            'type' => 'send-company-email',
        ])->sendMailRegistrationCompany()
            ->create()
            ->actionSettings;

        $newSettings = [
            'recipients' => ['to' => ['static' => ['mailables' => [
                ['recipient_id' => User::factory()->create()->id, 'recipient_type' => 'user'],
            ]]]],
            'test' => 'foo',
        ];

        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();
        $this->actingAs($user)->putJson("custom/action-settings/$actionSettings->id", [
            'settings' => $newSettings,
        ])->assertOk()
            ->assertJson([
                'data' => [
                    'id' => $actionSettings->id,
                    'settings' => $newSettings,
                ],
            ]);

        $this->assertEquals($newSettings, ActionSettings::findOrFail($actionSettings->id)->settings);
    }

    public function test_update_manual_action_settings_missing_required()
    {
        $actionSettings = ManualAction::factory([
            'type' => 'send-company-email',
        ])->sendMailRegistrationCompany()
            ->create()
            ->actionSettings;

        $newSettings = [
            'recipients' => ['to' => ['static' => ['mailables' => [
                ['recipient_id' => User::factory()->create()->id, 'recipient_type' => 'user'],
            ]]]],
        ];

        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();
        $this->actingAs($user)->putJson("custom/action-settings/$actionSettings->id", [
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
        $actionSettings = ActionSettings::factory([
            'settings' => [],
        ])->withEventAction('send-email')->create();
        $newSettings = [
            'recipients' => ['to' => ['bindings' => ['mailables' => ['user']]]],
        ];

        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();
        $this->actingAs($user)->putJson("custom/action-settings/$actionSettings->id", [
            'settings' => $newSettings,
        ])->assertOk()
            ->assertJson([
                'data' => [
                    'id' => $actionSettings->id,
                    'settings' => $newSettings,
                ],
            ]);

        $this->assertEquals($newSettings, ActionSettings::findOrFail($actionSettings->id)->settings);
    }

    public function test_update_action_settings_forbidden()
    {
        $actionSettings = ActionSettings::factory([
            'settings' => [],
        ])->withEventAction('send-email')->create();

        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user)->putJson("custom/action-settings/$actionSettings->id")
            ->assertForbidden();
    }
}
