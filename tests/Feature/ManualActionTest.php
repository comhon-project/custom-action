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

    public function testGetManualActionNotCreated()
    {
        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();
        $response = $this->actingAs($user)->getJson('custom/manual-actions/send-company-email');
        $response->assertJson([
            'data' => [
                'type' => 'send-company-email',
                'action_settings' => [
                    'settings' => [],
                ],
            ],
        ]);
    }

    public function testGetManualActionCreated()
    {
        $actionSettings = ManualAction::factory([
            'type' => 'send-company-email',
        ])->sendMailRegistrationCompany()
            ->create()
            ->actionSettings;
        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();

        $response = $this->actingAs($user)->getJson('custom/manual-actions/send-company-email');
        $response->assertJson([
            'data' => [
                'type' => 'send-company-email',
                'action_settings' => [
                    'id' => $actionSettings->id,
                    'settings' => [
                        'recipients' => ['to' => ['bindings' => ['mailables' => ['user']]]],
                        'attachments' => null,
                    ],
                ],
            ],
        ]);

        $response = $this->actingAs($user)->getJson('custom/action-settings/'.$actionSettings->id);
        $response->assertJson([
            'data' => [
                'id' => $actionSettings->id,
                'settings' => [
                    'recipients' => ['to' => ['bindings' => ['mailables' => ['user']]]],
                    'attachments' => null,
                ],
            ],
        ]);
    }

    public function testGetActionNotFound()
    {
        CustomActionModelResolver::register([], true);
        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();
        $response = $this->actingAs($user)->getJson('custom/manual-actions/send-email');
        $response->assertNotFound();
        $response->assertJson([
            'message' => 'not found',
        ]);
    }

    public function testGetActionSettingsForbidden()
    {
        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user)->getJson('custom/manual-actions/send-company-email')
            ->assertForbidden();
    }
}
