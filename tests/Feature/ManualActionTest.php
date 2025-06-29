<?php

namespace Tests\Feature;

use App\Actions\ComplexManualAction;
use App\Actions\SimpleManualAction;
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
        $this->actingAs($user)->getJson('custom/manual-actions/simple-manual-action')
            ->assertJson([
                'data' => [
                    'type' => 'simple-manual-action',
                    'default_setting' => null,
                ],
            ]);
    }

    public function test_get_manual_action_created()
    {
        $defaultSetting = $this->getActionDefaultSetting(SimpleManualAction::class);
        $user = User::factory()->hasConsumerAbility()->create();

        $this->actingAs($user)->getJson('custom/manual-actions/simple-manual-action')
            ->assertJson([
                'data' => [
                    'type' => 'simple-manual-action',
                    'default_setting' => [
                        'id' => $defaultSetting->id,
                        'settings' => [
                            'text' => 'simple text',
                        ],
                    ],
                ],
            ]);
    }

    public function test_get_action_not_found()
    {
        CustomActionModelResolver::register([], true);
        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();
        $this->actingAs($user)->getJson('custom/manual-actions/simple-event-action')
            ->assertNotFound()
            ->assertJson([
                'message' => 'not found',
            ]);
    }

    public function test_get_action_settings_forbidden()
    {
        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user)->getJson('custom/manual-actions/simple-manual-action')
            ->assertForbidden();
    }

    public function test_simulate_manual_action_success()
    {
        $inputs = [
            'settings' => ['text' => 'value'],
            'localized_settings' => ['localized_text' => 'localized value'],
        ];

        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();
        $this->actingAs($user)->postJson('custom/manual-actions/complex-manual-action/simulate', $inputs)
            ->assertOk()
            ->assertJson([
                'data' => [
                    'success' => true,
                    'result' => [
                        'output' => [
                            'text' => 'value',
                            'localized_text' => 'localized value',
                            'user_translation' => 'english status : foo',
                        ],
                    ],
                ],
            ]);
    }

    public function test_simulate_manual_action_with_state_success()
    {
        $inputs = [
            'settings' => ['text' => 'value'],
            'localized_settings' => ['localized_text' => 'localized value'],
            'states' => [
                ['status_1', 'status_2'],
            ],
        ];

        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();
        $this->actingAs($user)->postJson('custom/manual-actions/complex-manual-action/simulate', $inputs)
            ->assertOk()
            ->assertJson([
                'data' => [
                    [
                        'success' => true,
                        'result' => [
                            'output' => [
                                'text' => 'value',
                                'localized_text' => 'localized value',
                                'user_translation' => 'english status : foo',
                            ],
                        ],
                    ],
                ],
            ]);
    }

    #[DataProvider('provider_simulate_action_with_state_invalid_states')]
    public function test_simulate_manual_action_with_state_invalid_states($state, $error)
    {
        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();

        $inputs = [
            'settings' => ['text' => 'value'],
            'localized_settings' => [
                'localized_text' => 'localized value',
            ],
            'states' => $state,
        ];

        $this->actingAs($user)->postJson('custom/manual-actions/complex-manual-action/simulate', $inputs)
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

    public function test_simulate_manual_action_without_query_params()
    {
        ManualAction::factory()->action(ComplexManualAction::class)->create();

        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();

        $this->actingAs($user)->postJson('custom/manual-actions/complex-manual-action/simulate')
            ->assertOk();
    }

    public function test_simulate_manual_action_not_simulatable()
    {
        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();

        $this->actingAs($user)->postJson('custom/manual-actions/fakable-not-simulatable-action/simulate')
            ->assertUnprocessable()
            ->assertJson([
                'message' => 'cannot simulate action fakable-not-simulatable-action',
            ]);
    }

    public function test_simulate_manual_action_not_fakable()
    {
        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();

        $this->actingAs($user)->postJson('custom/manual-actions/simulatable-not-fakable-action/simulate')
            ->assertUnprocessable()
            ->assertJson([
                'message' => 'cannot simulate action, action simulatable-not-fakable-action is not fakable',
            ]);
    }

    public function test_simulate_manual_action_forbidden()
    {
        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user)->postJson('custom/manual-actions/complex-manual-action/simulate')
            ->assertForbidden();
    }
}
