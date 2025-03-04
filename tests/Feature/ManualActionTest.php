<?php

namespace Tests\Feature;

use App\Models\User;
use Comhon\CustomAction\Facades\CustomActionModelResolver;
use Comhon\CustomAction\Models\ManualAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\SetUpWithModelRegistrationTrait;
use Tests\TestCase;

class ManualActionTest extends TestCase
{
    use RefreshDatabase;
    use SetUpWithModelRegistrationTrait;

    public function test_get_manual_action_not_created()
    {
        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();
        $response = $this->actingAs($user)->getJson('custom/manual-actions/send-manual-company-email');
        $response->assertJson([
            'data' => [
                'type' => 'send-manual-company-email',
                'default_setting' => null,
            ],
        ]);
    }

    public function test_get_manual_action_created()
    {
        $defaultSetting = ManualAction::factory([
            'type' => 'send-manual-company-email',
        ])->sendMailRegistrationCompany()
            ->create()
            ->defaultSetting;
        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();

        $response = $this->actingAs($user)->getJson('custom/manual-actions/send-manual-company-email');
        $response->assertJson([
            'data' => [
                'type' => 'send-manual-company-email',
                'default_setting' => [
                    'id' => $defaultSetting->id,
                    'settings' => [
                        'recipients' => ['to' => ['bindings' => ['mailables' => ['user']]]],
                        'attachments' => null,
                    ],
                ],
            ],
        ]);

        $response = $this->actingAs($user)->getJson('custom/default-settings/'.$defaultSetting->id);
        $response->assertJson([
            'data' => [
                'id' => $defaultSetting->id,
                'settings' => [
                    'recipients' => ['to' => ['bindings' => ['mailables' => ['user']]]],
                    'attachments' => null,
                ],
            ],
        ]);
    }

    public function test_get_action_not_found()
    {
        CustomActionModelResolver::register([], true);
        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();
        $response = $this->actingAs($user)->getJson('custom/manual-actions/send-automatic-email');
        $response->assertNotFound();
        $response->assertJson([
            'message' => 'not found',
        ]);
    }

    public function test_get_action_settings_forbidden()
    {
        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user)->getJson('custom/manual-actions/send-manual-company-email')
            ->assertForbidden();
    }
}
