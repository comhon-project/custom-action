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
                        'recipients' => ['to' => ['context' => ['mailables' => ['user']]]],
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
                    'recipients' => ['to' => ['context' => ['mailables' => ['user']]]],
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

    public function test_simulate_action_success()
    {
        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();

        $inputs = [
            'settings' => ['test' => 'value'],
            'localized_settings' => [
                'subject' => 'subject company {{ company.status }}',
                'body' => 'body company {{ company.status }}',
            ],
        ];

        $this->actingAs($user)->postJson('custom/manual-actions/send-manual-company-email-with-context-translations/simulate', $inputs)
            ->assertOk()
            ->assertJson([
                'data' => [
                    'subject' => 'subject company draft',
                    'body' => 'body company draft',
                ],
            ]);
    }

    public function test_simulate_action_without_query_params()
    {
        $action = ManualAction::factory([
            'type' => 'send-manual-company-email-with-context-translations',
        ])->sendMailRegistrationCompany()
            ->create();

        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();

        $this->actingAs($user)->postJson('custom/manual-actions/send-manual-company-email-with-context-translations/simulate')
            ->assertOk();
    }

    public function test_simulate_action_not_simulatable()
    {
        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();

        $this->actingAs($user)->postJson('custom/manual-actions/my-manual-action-without-context/simulate')
            ->assertUnprocessable()
            ->assertJson([
                'message' => 'cannot simulate action my-manual-action-without-context',
            ]);
    }

    public function test_simulate_action_not_fakable()
    {
        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();

        $inputs = [
            'settings' => ['test' => 'value'],
            'localized_settings' => [
                'subject' => 'subject company {{ company.status }}',
                'body' => 'body company {{ company.status }}',
            ],
        ];

        $this->actingAs($user)->postJson('custom/manual-actions/send-manual-company-email/simulate', $inputs)
            ->assertUnprocessable()
            ->assertJson([
                'message' => 'cannot simulate action, action send-manual-company-email is not fakable',
            ]);
    }

    public function test_simulate_action_forbidden()
    {
        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user)->postJson('custom/manual-actions/send-manual-company-email-with-context-translations/simulate')
            ->assertForbidden();
    }
}
