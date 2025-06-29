<?php

namespace Tests\Feature\SendEmail;

use App\Models\User;
use Comhon\CustomAction\Rules\RuleHelper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\SetUpWithModelRegistrationTrait;
use Tests\TestCase;

class SendGenericEmailSchemaTest extends TestCase
{
    use RefreshDatabase;
    use SetUpWithModelRegistrationTrait;

    public function test_get_action_shema_success()
    {
        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();
        $params = http_build_query(['event_context' => 'my-email-event']);
        $this->actingAs($user)->getJson("custom/actions/send-automatic-email/schema?$params")
            ->assertJson([
                'data' => [
                    'context_schema' => [
                        'to' => ['nullable', 'is:mailable-entity'],
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
                    'simulatable' => false,
                    'fake_state_schema' => null,
                ],
            ]);
    }
}
