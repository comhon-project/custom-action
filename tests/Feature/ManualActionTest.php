<?php

namespace Tests\Feature;

use App\Models\User;
use Comhon\CustomAction\Facades\CustomActionModelResolver;
use Comhon\CustomAction\Models\ManualAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
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
                    'success' => true,
                    'result' => [
                        [
                            'to' => [],
                            'cc' => [],
                            'bcc' => [],
                            'subject' => 'subject company draft',
                            'body' => 'body company draft',
                        ],
                    ],
                ],
            ]);
    }

    public function test_simulate_action_with_state_success()
    {
        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();

        $inputs = [
            'settings' => ['test' => 'value'],
            'localized_settings' => [
                'subject' => 'subject company {{ company.status }}',
                'body' => 'body company {{ company.status }}',
            ],
            'states' => [
                ['status_1', 'status_2'],
            ],
        ];

        $this->actingAs($user)->postJson('custom/manual-actions/send-manual-company-email-with-context-translations/simulate', $inputs)
            ->assertOk()
            ->assertJson([
                'data' => [
                    [
                        'success' => true,
                        'result' => [
                            [
                                'to' => [],
                                'subject' => 'subject company -status_1-status_2',
                                'body' => 'body company -status_1-status_2',
                            ],
                        ],
                    ],
                ],
            ]);
    }

    #[DataProvider('provider_simulate_action_with_state_invalid_states')]
    public function test_simulate_action_with_state_invalid_states($state, $error)
    {
        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();

        $inputs = [
            'settings' => ['test' => 'value'],
            'localized_settings' => [
                'subject' => 'subject company {{ company.status }}',
                'body' => 'body company {{ company.status }}',
            ],
            'states' => $state,
        ];

        $this->actingAs($user)->postJson('custom/manual-actions/send-manual-company-email-with-context-translations/simulate', $inputs)
            ->assertUnprocessable()
            ->assertJson([
                'message' => $error,
                'errors' => [
                    'states' => [
                        $error,
                    ],
                ],
            ]);
    }

    public static function provider_simulate_action_with_state_invalid_states()
    {
        return [
            ['foo', 'The states field must be an array.'],
            [['foo'], 'The states.0 is invalid.'],
            [[1], 'The states.0 must be a string or an array.'],
            [[['status' => 1]], 'The states.0 is invalid.'],
            [[['foo' => 1]], 'The states.0 is invalid.'],
            [[['status' => 10, 'foo']], 'The states.0 is invalid.'],
            [
                [[
                    ['status_1', ['status_2']],
                ]],
                'The states.0.0.1 is invalid.',
            ],
        ];
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
