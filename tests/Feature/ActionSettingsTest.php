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

    public function testGetActionSettingsSuccess()
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

    public function testGetActionSettingsForbidden()
    {
        $actionSettings = ActionSettings::factory()->withManualAction()->create();
        /** @var User $consumer */
        $consumer = User::factory()->create();
        $this->actingAs($consumer)->getJson("custom/action-settings/{$actionSettings->id}")
            ->assertForbidden();
    }

    public function testUpdateGenericActionSettings()
    {
        $actionSettings = ActionSettings::factory([
            'settings' => [],
        ])->withEventAction('send-email')->create();
        $newSettings = [
            'to_receivers' => [
                ['receiver_id' => User::factory()->create()->id, 'receiver_type' => 'user'],
            ],
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

    public function testUpdateManualActionSettings()
    {
        $actionSettings = ManualAction::factory([
            'type' => 'send-company-email',
        ])->sendMailRegistrationCompany()
            ->create()
            ->actionSettings;

        $newSettings = [
            'to_receivers' => [
                ['receiver_id' => User::factory()->create()->id, 'receiver_type' => 'user'],
            ],
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

    public function testUpdateManualActionSettingsMissingRequired()
    {
        $actionSettings = ManualAction::factory([
            'type' => 'send-company-email',
        ])->sendMailRegistrationCompany()
            ->create()
            ->actionSettings;

        $newSettings = [
            'to_receivers' => [
                ['receiver_id' => User::factory()->create()->id, 'receiver_type' => 'user'],
            ],
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

    public function testUpdateActionWithEventContextSettings()
    {
        $actionSettings = ActionSettings::factory([
            'settings' => [],
        ])->withEventAction('send-email')->create();
        $newSettings = [
            'to_bindings_receivers' => ['user'],
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

    public function testUpdateActionSettingsForbidden()
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
