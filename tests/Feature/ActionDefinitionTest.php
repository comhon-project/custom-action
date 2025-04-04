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
                'context_schema' => [
                    'to' => 'is:mailable-entity',
                    'default_timezone' => 'string',
                    'preferred_timezone' => 'string',
                ],
                'translatable_context' => [],
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
                'context_keys_ignored_for_scoped_setting' => [
                    'to', 'default_timezone', 'preferred_timezone',
                ],
            ],
        ]);

        $response = $this->actingAs($user)->getJson('custom/actions/send-manual-company-email-with-context-translations/schema');
        $response->assertJson([
            'data' => [
                'context_schema' => [
                    'to' => 'is:mailable-entity',
                    'default_timezone' => 'string',
                    'preferred_timezone' => 'string',
                    'company.name' => 'string',
                    'company.status' => 'string',
                    'company.languages.*.locale' => 'string',
                    'logo' => 'is:stored-file',
                ],
                'translatable_context' => [
                    'company.status',
                    'company.languages.*.locale',
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
                'context_keys_ignored_for_scoped_setting' => [
                    'to', 'default_timezone', 'preferred_timezone',
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
                    'context_schema' => [
                        'to' => 'is:mailable-entity',
                        'default_timezone' => 'string',
                        'preferred_timezone' => 'string',
                    ],
                    'translatable_context' => [],
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
                        'from.context.mailable' => 'string|in:user',
                        'from.context.email' => 'string|in:user.email,responsibles.*.email',
                        'recipients.to.context.mailables' => 'array',
                        'recipients.to.context.mailables.*' => 'string|in:user',
                        'recipients.to.context.emails' => 'array',
                        'recipients.to.context.emails.*' => 'string|in:user.email,responsibles.*.email',
                        'recipients.cc.context.mailables' => 'array',
                        'recipients.cc.context.mailables.*' => 'string|in:user',
                        'recipients.cc.context.emails' => 'array',
                        'recipients.cc.context.emails.*' => 'string|in:user.email,responsibles.*.email',
                        'recipients.bcc.context.mailables' => 'array',
                        'recipients.bcc.context.mailables.*' => 'string|in:user',
                        'recipients.bcc.context.emails' => 'array',
                        'recipients.bcc.context.emails.*' => 'string|in:user.email,responsibles.*.email',
                    ],
                    'localized_settings_schema' => [
                        'subject' => 'required|'.RuleHelper::getRuleName('text_template'),
                        'body' => 'required|'.RuleHelper::getRuleName('html_template'),
                    ],
                    'context_keys_ignored_for_scoped_setting' => [
                        'to', 'default_timezone', 'preferred_timezone',
                    ],
                ],
            ]);
    }

    public function test_get_action_shema_without_context_success()
    {
        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();

        $this->actingAs($user)->getJson('custom/actions/my-action-without-context/schema')
            ->assertOk()
            ->assertJson([
                'data' => [
                    'context_schema' => [],
                    'translatable_context' => [],
                    'settings_schema' => [],
                    'localized_settings_schema' => [],
                    'context_keys_ignored_for_scoped_setting' => [],
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
