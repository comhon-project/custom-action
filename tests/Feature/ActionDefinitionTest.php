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
                'send-manual-company-email',
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

    public function test_get_action_shema_success()
    {
        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();

        $response = $this->actingAs($user)->getJson('custom/actions/send-automatic-email/schema');
        $response->assertJson([
            'data' => [
                'bindings_schema' => [
                    'to' => 'is:mailable-entity',
                    'default_timezone' => 'string',
                    'preferred_timezone' => 'string',

                ],
                'settings_schema' => [
                    'from.static.mailable' => 'model_reference:mailable-entity,from',
                    'from.static.email' => 'email',
                    'recipients.to.static.mailables' => 'array',
                    'recipients.to.static.mailables.*' => 'model_reference:mailable-entity,recipient',
                    'recipients.to.static.emails' => 'array',
                    'recipients.to.static.emails.*' => 'email',
                    'recipients.cc.static.mailables' => 'array',
                    'recipients.cc.static.mailables.*' => 'model_reference:mailable-entity,recipient',
                    'recipients.cc.static.emails' => 'array',
                    'recipients.cc.static.emails.*' => 'email',
                    'recipients.bcc.static.mailables' => 'array',
                    'recipients.bcc.static.mailables.*' => 'model_reference:mailable-entity,recipient',
                    'recipients.bcc.static.emails' => 'array',
                    'recipients.bcc.static.emails.*' => 'email',
                ],
                'localized_settings_schema' => [
                    'subject' => 'required|'.RuleHelper::getRuleName('text_template'),
                    'body' => 'required|'.RuleHelper::getRuleName('html_template'),
                ],
            ],
        ]);

        $response = $this->actingAs($user)->getJson('custom/actions/send-manual-company-email/schema');
        $response->assertJson([
            'data' => [
                'bindings_schema' => [
                    'to' => 'is:mailable-entity',
                    'default_timezone' => 'string',
                    'preferred_timezone' => 'string',
                    'company.name' => 'string',
                    'logo' => 'is:stored-file',
                ],
                'settings_schema' => [
                    'recipients.to.static.mailables' => 'array',
                    'recipients.to.static.mailables.*' => 'model_reference:mailable-entity,recipient',
                    'test' => 'required|string',
                ],
                'localized_settings_schema' => [
                    'subject' => 'required|'.RuleHelper::getRuleName('text_template'),
                    'body' => 'required|'.RuleHelper::getRuleName('html_template'),
                    'test_localized' => 'string',
                ],
            ],
        ]);
    }

    public function test_get_action_shema_with_context_success()
    {
        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();

        $params = http_build_query(['event_context' => 'company-registered']);
        $this->actingAs($user)->getJson("custom/actions/send-automatic-email/schema?$params")
            ->assertOk()
            ->assertJson([
                'data' => [
                    'bindings_schema' => [
                        'to' => 'is:mailable-entity',
                        'default_timezone' => 'string',
                        'preferred_timezone' => 'string',
                    ],
                    'settings_schema' => [
                        'from.static.mailable' => 'model_reference:mailable-entity,from',
                        'from.static.email' => 'email',
                        'recipients.to.static.mailables' => 'array',
                        'recipients.to.static.mailables.*' => 'model_reference:mailable-entity,recipient',
                        'recipients.to.static.emails' => 'array',
                        'recipients.to.static.emails.*' => 'email',
                        'recipients.cc.static.mailables' => 'array',
                        'recipients.cc.static.mailables.*' => 'model_reference:mailable-entity,recipient',
                        'recipients.cc.static.emails' => 'array',
                        'recipients.cc.static.emails.*' => 'email',
                        'recipients.bcc.static.mailables' => 'array',
                        'recipients.bcc.static.mailables.*' => 'model_reference:mailable-entity,recipient',
                        'recipients.bcc.static.emails' => 'array',
                        'recipients.bcc.static.emails.*' => 'email',
                        'attachments' => 'array',
                        'attachments.*' => 'string|in:logo',
                        'from.bindings.mailable' => 'string|in:user',
                        'from.bindings.email' => 'string|in:user.email,responsibles.*.email',
                        'recipients.to.bindings.mailables' => 'array',
                        'recipients.to.bindings.mailables.*' => 'string|in:user',
                        'recipients.to.bindings.emails' => 'array',
                        'recipients.to.bindings.emails.*' => 'string|in:user.email,responsibles.*.email',
                        'recipients.cc.bindings.mailables' => 'array',
                        'recipients.cc.bindings.mailables.*' => 'string|in:user',
                        'recipients.cc.bindings.emails' => 'array',
                        'recipients.cc.bindings.emails.*' => 'string|in:user.email,responsibles.*.email',
                        'recipients.bcc.bindings.mailables' => 'array',
                        'recipients.bcc.bindings.mailables.*' => 'string|in:user',
                        'recipients.bcc.bindings.emails' => 'array',
                        'recipients.bcc.bindings.emails.*' => 'string|in:user.email,responsibles.*.email',
                    ],
                    'localized_settings_schema' => [
                        'subject' => 'required|'.RuleHelper::getRuleName('text_template'),
                        'body' => 'required|'.RuleHelper::getRuleName('html_template'),
                    ],
                ],
            ]);
    }

    public function test_get_action_shema_without_bindings_success()
    {
        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();

        $this->actingAs($user)->getJson('custom/actions/my-action-without-bindings/schema')
            ->assertOk()
            ->assertJson([
                'data' => [
                    'bindings_schema' => [],
                    'settings_schema' => [],
                    'localized_settings_schema' => [],
                ],
            ]);
    }

    #[DataProvider('providerBadEventContext')]
    public function test_get_action_shema_with_invalid_context($eventContext)
    {
        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();

        $params = http_build_query(['event_context' => $eventContext]);
        $this->actingAs($user)->getJson("custom/actions/send-automatic-email/schema?$params")
            ->assertUnprocessable()
            ->assertJson([
                'message' => 'The event context is not subclass of custom-event.',
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

        $response = $this->actingAs($user)->getJson('custom/actions/send-automatic-email/schema');
        $response->assertNotFound();
    }

    public function test_get_action_shema_forbidden()
    {
        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user)->getJson('custom/actions/send-automatic-email/schema')
            ->assertForbidden();
    }
}
