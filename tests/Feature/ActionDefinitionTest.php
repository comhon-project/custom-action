<?php

namespace Tests\Feature;

use App\Models\User;
use Comhon\CustomAction\Facades\CustomActionModelResolver;
use Comhon\CustomAction\Rules\RuleHelper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\SetUpWithModelRegistrationTrait;
use Tests\TestCase;

class ActionDefinitionTest extends TestCase
{
    use RefreshDatabase;
    use SetUpWithModelRegistrationTrait;

    public function test_get_actions_success()
    {
        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();
        $response = $this->actingAs($user)->getJson('custom/manual-actions');
        $response->assertJson([
            'data' => [
                'simple-manual-action',
            ],
        ]);
    }

    public function test_get_actions_forbidden()
    {
        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user)->getJson('custom/manual-actions')
            ->assertForbidden();
    }

    #[DataProvider('providerBoolean')]
    public function test_get_event_action_shema_success($withContextEvent)
    {
        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();

        $params = $withContextEvent
            ? http_build_query(['event_context' => 'my-complex-event'])
            : '';
        $this->actingAs($user)->getJson("custom/actions/complex-event-action/schema?$params")
            ->assertJson([
                'data' => [
                    'context_schema' => [],
                    'translatable_context' => [],
                    'settings_schema' => [
                        'text' => ['required', RuleHelper::getRuleName('text_template')],
                        'emails' => 'array',
                        'emails.*' => $withContextEvent
                            ? 'string|in:user.email,actionEmail'
                            : 'string|in:actionEmail',
                    ],
                    'localized_settings_schema' => [
                        'localized_text' => ['required', RuleHelper::getRuleName('html_template')],
                    ],
                    'context_keys_ignored_for_scoped_setting' => [
                        'ignoredEmail',
                    ],
                    'simulatable' => $withContextEvent,
                    'fake_state_schema' => $withContextEvent
                        ? [
                            'status_1',
                            'status_2',
                            'status_3',
                            'status' => 'integer|min:10',
                        ]
                        : null,
                ],
            ]);
    }

    public function test_get_manual_action_shema_success()
    {
        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();

        $this->actingAs($user)->getJson('custom/actions/complex-manual-action/schema')
            ->assertJson([
                'data' => [
                    'context_schema' => [],
                    'translatable_context' => [],
                    'settings_schema' => [
                        'text' => ['required', RuleHelper::getRuleName('text_template')],
                    ],
                    'localized_settings_schema' => [
                        'localized_text' => ['required', RuleHelper::getRuleName('html_template')],
                    ],
                    'context_keys_ignored_for_scoped_setting' => [],
                    'simulatable' => true,
                    'fake_state_schema' => [
                        'status_1',
                        'status_2',
                        'status_3',
                        'status' => 'integer|min:10',
                    ],
                ],
            ]);
    }

    public function test_get_action_empty_shema_success()
    {
        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();

        $this->actingAs($user)->getJson('custom/actions/simple-manual-action/schema')
            ->assertOk()
            ->assertJson([
                'data' => [
                    'context_schema' => [],
                    'translatable_context' => [],
                    'settings_schema' => [],
                    'localized_settings_schema' => [],
                    'context_keys_ignored_for_scoped_setting' => [],
                    'simulatable' => false,
                    'fake_state_schema' => null,
                ],
            ]);
    }

    #[DataProvider('providerBadEventContext')]
    public function test_get_action_shema_with_invalid_context($eventContext)
    {
        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();

        $params = http_build_query(['event_context' => $eventContext]);
        $this->actingAs($user)->getJson("custom/actions/simple-event-action/schema?$params")
            ->assertUnprocessable()
            ->assertJson([
                'message' => 'The event_context is not subclass of custom-event.',
            ]);
    }

    public static function providerBadEventContext()
    {
        return [
            ['stored-file'],
            ['custom-event'],
        ];
    }

    public function test_get_action_shema_not_found()
    {
        CustomActionModelResolver::register([], true);
        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();

        $response = $this->actingAs($user)->getJson('custom/actions/simple-event-action/schema');
        $response->assertNotFound();
    }

    public function test_get_action_shema_forbidden()
    {
        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user)->getJson('custom/actions/simple-event-action/schema')
            ->assertForbidden();
    }
}
