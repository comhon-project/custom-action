<?php

namespace Tests\Feature;

use App\Actions\SendCompanyRegistrationMail;
use App\Models\User;
use Comhon\CustomAction\Facades\CustomActionModelResolver;
use Comhon\CustomAction\Rules\RuleHelper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\SetUpWithModelRegistrationTrait;
use Tests\TestCase;

class ActionDefinitionTest extends TestCase
{
    use RefreshDatabase;
    use SetUpWithModelRegistrationTrait;

    public function testGetActionsSuccess()
    {
        config(['custom-action.manual_actions' => [SendCompanyRegistrationMail::class]]);
        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();
        $response = $this->actingAs($user)->getJson('custom/action-types/manual');
        $response->assertJson([
            'data' => [
                [
                    'type' => 'send-company-email',
                    'name' => 'send company email',
                ],
            ],
        ]);
    }

    public function testGetActionsForbidden()
    {
        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user)->getJson('custom/action-types/manual')
            ->assertForbidden();
    }

    public function testGetActionShemaSuccess()
    {
        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();

        $response = $this->actingAs($user)->getJson('custom/action-types/send-email/schema');
        $response->assertJson([
            'data' => [
                'binding_schema' => [
                    'to' => 'is:mailable-entity',
                ],
                'settings_schema' => [
                    'to_receivers' => 'array',
                    'to_receivers.*' => 'model_reference:mailable-entity,receiver',
                    'to_emails' => 'array',
                    'to_emails.*' => 'email',
                ],
                'localized_settings_schema' => [
                    'subject' => 'required|'.RuleHelper::getRuleName('text_template'),
                    'body' => 'required|'.RuleHelper::getRuleName('html_template'),
                ],
            ],
        ]);

        $response = $this->actingAs($user)->getJson('custom/action-types/send-company-email/schema');
        $response->assertJson([
            'data' => [
                'binding_schema' => [
                    'to' => 'is:mailable-entity',
                    'company.name' => 'string',
                    'logo' => 'is:stored-file',
                ],
                'settings_schema' => [
                    'to_receivers' => 'array',
                    'to_receivers.*' => 'model_reference:mailable-entity,receiver',
                    'to_emails' => 'array',
                    'to_emails.*' => 'email',
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

    public function testGetActionShemaWithContextSuccess()
    {
        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();

        $params = http_build_query(['event_context' => 'company-registered']);
        $this->actingAs($user)->getJson("custom/action-types/send-email/schema?$params")
            ->assertOk()
            ->assertJson([
                'data' => [
                    'binding_schema' => [
                        'to' => 'is:mailable-entity',
                    ],
                    'settings_schema' => [
                        'to_receivers' => 'array',
                        'to_receivers.*' => 'model_reference:mailable-entity,receiver',
                        'to_emails' => 'array',
                        'to_emails.*' => 'email',

                        'to_bindings_receivers' => 'array',
                        'to_bindings_receivers.*' => 'string|in:user',
                        'to_bindings_emails' => 'array',
                        'to_bindings_emails.*' => 'string|in:user.email,responsibles.*.email',
                        'attachments' => 'array',
                        'attachments.*' => 'string|in:logo',
                    ],
                    'localized_settings_schema' => [
                        'subject' => 'required|'.RuleHelper::getRuleName('text_template'),
                        'body' => 'required|'.RuleHelper::getRuleName('html_template'),
                    ],
                ],
            ]);
    }

    public function testGetActionShemaWithoutBindingsSuccess()
    {
        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();

        $this->actingAs($user)->getJson('custom/action-types/my-action-without-bindings/schema')
            ->assertOk()
            ->assertJson([
                'data' => [
                    'binding_schema' => [],
                    'settings_schema' => [],
                    'localized_settings_schema' => [],
                ],
            ]);
    }

    public function testGetActionShemaWithInvalidContext()
    {
        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();

        $params = http_build_query(['event_context' => 'stored-file']);
        $this->actingAs($user)->getJson("custom/action-types/send-email/schema?$params")
            ->assertUnprocessable()
            ->assertJson([
                'message' => 'The event context is not subclass of custom-event.',
            ]);
    }

    public function testGetActionShemaWithInvalidContext2()
    {
        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();

        $params = http_build_query(['event_context' => 'custom-event']);
        $this->actingAs($user)->getJson("custom/action-types/send-email/schema?$params")
            ->assertUnprocessable()
            ->assertJson([
                'message' => 'The event context is not subclass of custom-event.',
            ]);
    }

    public function testGetActionShemaNotFound()
    {
        CustomActionModelResolver::register([], true);
        /** @var User $user */
        $user = User::factory()->hasConsumerAbility()->create();

        $response = $this->actingAs($user)->getJson('custom/action-types/send-email/schema');
        $response->assertNotFound();
    }

    public function testGetActionShemaForbidden()
    {
        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs($user)->getJson('custom/action-types/send-email/schema')
            ->assertForbidden();
    }
}
