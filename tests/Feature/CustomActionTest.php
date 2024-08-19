<?php

namespace Tests\Feature;

use App\Actions\SendCompanyRegistrationMail;
use App\Models\Company;
use App\Models\User;
use App\Models\UserWithoutPreference;
use Comhon\CustomAction\BindingsContainer;
use Comhon\CustomAction\Facades\CustomActionModelResolver;
use Comhon\CustomAction\Files\SystemFile;
use Comhon\CustomAction\Mail\Custom;
use Comhon\CustomAction\Models\ActionLocalizedSettings;
use Comhon\CustomAction\Models\ActionScopedSettings;
use Comhon\CustomAction\Models\ActionSettings;
use Comhon\CustomAction\Models\ActionSettingsContainer;
use Comhon\CustomAction\Models\EventAction;
use Comhon\CustomAction\Models\ManualAction;
use Comhon\CustomAction\Rules\RuleHelper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;
use Tests\SetUpWithModelRegistration;
use Tests\Support\Utils;
use Tests\TestCase;

class CustomActionTest extends TestCase
{
    use RefreshDatabase;
    use SetUpWithModelRegistration;

    private function getAssetPath(): string
    {
        return Utils::joinPaths(Utils::getTestPath('Data'), 'jc.jpeg');
    }

    /**
     * @dataProvider providerHandleManualActionSuccess
     */
    public function testHandleManualActionSuccess($preferredLocale, $appLocale, $fallbackLocale, $success)
    {
        App::setLocale($appLocale);
        App::setFallbackLocale($fallbackLocale);
        $user = User::factory(['preferred_locale' => $preferredLocale])->create();
        $company = Company::factory()->create();
        ManualAction::factory()->sendMailRegistrationCompany(null, false, true)->create();

        $bindings = ['company' => $company, 'logo' => new SystemFile($this->getAssetPath())];

        Mail::fake();

        if (! $success) {
            $this->expectExceptionMessage('localized mail values not found');
        }
        SendCompanyRegistrationMail::handleManual(new BindingsContainer($bindings), $user);

        $mails = [];
        Mail::assertSent(Custom::class, 1);
        Mail::assertSent(Custom::class, function (Custom $mail) use (&$mails) {
            $mails[] = $mail;

            return true;
        });
        $mails[0]->assertHasTo($user->email);
        $mails[0]->assertHasSubject(
            "Dear $user->first_name, company $company->name  (login: December 12, 2022 at 12:00 AM (UTC) December 12, 2022 at 12:00 AM (UTC))"
        );
        $this->assertTrue($mails[0]->hasAttachment(Attachment::fromPath($this->getAssetPath())));
    }

    public static function providerHandleManualActionSuccess()
    {
        return [
            ['en', 'fr', 'fr', true],
            ['es', 'en', 'fr', true],
            ['es', 'es', 'en', true],
            ['es', 'es', 'es', false],
        ];
    }

    /**
     * @dataProvider providerHandleManualActionUserWithoutPreferencesSuccess
     */
    public function testHandleManualActionUserWithoutPreferencesSuccess($appLocale, $fallbackLocale, $success)
    {
        App::setLocale($appLocale);
        App::setFallbackLocale($fallbackLocale);
        $user = UserWithoutPreference::factory()->create();
        $company = Company::factory()->create();
        ManualAction::factory()->sendMailRegistrationCompany(null, false, true)->create();

        $bindings = ['company' => $company, 'logo' => new SystemFile($this->getAssetPath())];

        Mail::fake();

        if (! $success) {
            $this->expectExceptionMessage('localized mail values not found');
        }
        SendCompanyRegistrationMail::handleManual(new BindingsContainer($bindings), $user);

        $mails = [];
        Mail::assertSent(Custom::class, 1);
        Mail::assertSent(Custom::class, function (Custom $mail) use (&$mails) {
            $mails[] = $mail;

            return true;
        });
        $mails[0]->assertHasTo($user->email);
        $mails[0]->assertHasSubject(
            "Dear $user->first_name, company $company->name  (login: December 12, 2022 at 12:00 AM (UTC) December 12, 2022 at 12:00 AM (UTC))"
        );
        $this->assertTrue($mails[0]->hasAttachment(Attachment::fromPath($this->getAssetPath())));
    }

    /**
     * @dataProvider providerHandleManualActionUserWithoutPreferencesSuccess
     */
    public function testHandleManualActionUserWithBindingsContainerWithSchemaAllValid($appLocale, $fallbackLocale, $success)
    {
        App::setLocale($appLocale);
        App::setFallbackLocale($fallbackLocale);
        $user = UserWithoutPreference::factory()->create();
        $company = Company::factory()->create();
        ManualAction::factory()->sendMailRegistrationCompany(null, false, true)->create();

        $bindings = ['company' => $company, 'logo' => new SystemFile($this->getAssetPath())];
        $bindingSchema = [
            'company' => 'is:company',
            'logo' => 'is:stored-file',
        ];

        Mail::fake();

        if (! $success) {
            $this->expectExceptionMessage('localized mail values not found');
        }
        SendCompanyRegistrationMail::handleManual(new BindingsContainer($bindings, $bindingSchema), $user);

        $mails = [];
        Mail::assertSent(Custom::class, 1);
        Mail::assertSent(Custom::class, function (Custom $mail) use (&$mails) {
            $mails[] = $mail;

            return true;
        });
        $mails[0]->assertHasTo($user->email);
        $mails[0]->assertHasSubject(
            "Dear $user->first_name, company $company->name  (login: December 12, 2022 at 12:00 AM (UTC) December 12, 2022 at 12:00 AM (UTC))"
        );
        $this->assertTrue($mails[0]->hasAttachment(Attachment::fromPath($this->getAssetPath())));
    }

    public function testHandleManualActionUserWithBindingsContainerWithSchemaWithInvalid()
    {
        $user = UserWithoutPreference::factory()->create();
        ManualAction::factory()->sendMailRegistrationCompany(null, false, true)->create();

        $bindings = ['company' => $user, 'logo' => new SystemFile($this->getAssetPath())];
        $bindingSchema = [
            'company' => 'is:company',
            'logo' => 'is:stored-file',
        ];

        $this->expectExceptionMessage('The company is not instance of company.');
        SendCompanyRegistrationMail::handleManual(new BindingsContainer($bindings, $bindingSchema), $user);

    }

    /**
     * @dataProvider providerHandleManualActionUserWithoutPreferencesSuccess
     */
    public function testHandleManualActionUserWithoutBindingsContainer($appLocale, $fallbackLocale, $success)
    {
        App::setLocale($appLocale);
        App::setFallbackLocale($fallbackLocale);
        $user = UserWithoutPreference::factory()->create();
        $company = Company::factory()->create();
        ManualAction::factory()->sendMailRegistrationCompany(null, false, true)->create();

        Mail::fake();

        if (! $success) {
            $this->expectExceptionMessage('localized mail values not found');
        }
        SendCompanyRegistrationMail::handleManual(null, $user);

        $mails = [];
        Mail::assertSent(Custom::class, 1);
        Mail::assertSent(Custom::class, function (Custom $mail) use (&$mails) {
            $mails[] = $mail;

            return true;
        });
        $mails[0]->assertHasTo($user->email);
        $mails[0]->assertHasSubject(
            "Dear $user->first_name, company   (login: December 12, 2022 at 12:00 AM (UTC) December 12, 2022 at 12:00 AM (UTC))"
        );
    }

    public static function providerHandleManualActionUserWithoutPreferencesSuccess()
    {
        return [
            ['en', 'fr', true],
            ['es', 'en', true],
            ['es', 'es', false],
        ];
    }

    public function testHandleManualActionWithoutSettings()
    {
        $user = User::factory()->create();
        $company = Company::factory()->create();

        $bindings = ['company' => $company, 'logo' => new SystemFile($this->getAssetPath())];
        $this->expectExceptionMessage('No query results for model');
        SendCompanyRegistrationMail::handleManual(new BindingsContainer($bindings), $user);
    }

    public function testGetActionsSuccess()
    {
        config(['custom-action.manual_actions' => [SendCompanyRegistrationMail::class]]);
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
        $user = User::factory()->create();
        $this->actingAs($user)->getJson('custom/action-types/manual')
            ->assertForbidden();
    }

    public function testGetActionShemaSuccess()
    {
        $user = User::factory()->hasConsumerAbility()->create();

        $response = $this->actingAs($user)->getJson('custom/action-types/send-email/schema');
        $response->assertJson([
            'data' => [
                'binding_schema' => [
                    'to' => 'is:email-receiver',
                ],
                'settings_schema' => [
                    'to_receivers' => 'array',
                    'to_receivers.*' => 'model_reference:email-receiver,receiver',
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
                    'to' => 'is:email-receiver',
                    'company.name' => 'string',
                    'logo' => 'is:stored-file',
                ],
                'settings_schema' => [
                    'to_receivers' => 'array',
                    'to_receivers.*' => 'model_reference:email-receiver,receiver',
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
        $user = User::factory()->hasConsumerAbility()->create();

        $params = http_build_query(['event_context' => 'company-registered']);
        $this->actingAs($user)->getJson("custom/action-types/send-email/schema?$params")
            ->assertOk()
            ->assertJson([
                'data' => [
                    'binding_schema' => [
                        'to' => 'is:email-receiver',
                    ],
                    'settings_schema' => [
                        'to_receivers' => 'array',
                        'to_receivers.*' => 'model_reference:email-receiver,receiver',
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
        $user = User::factory()->hasConsumerAbility()->create();

        $response = $this->actingAs($user)->getJson('custom/action-types/send-email/schema');
        $response->assertNotFound();
    }

    public function testGetActionShemaForbidden()
    {
        $user = User::factory()->create();
        $this->actingAs($user)->getJson('custom/action-types/send-email/schema')
            ->assertForbidden();
    }

    public function testGetManualActionNotCreated()
    {
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
        $user = User::factory()->hasConsumerAbility()->create();

        $response = $this->actingAs($user)->getJson('custom/manual-actions/send-company-email');
        $response->assertJson([
            'data' => [
                'type' => 'send-company-email',
                'action_settings' => [
                    'id' => $actionSettings->id,
                    'settings' => [
                        'to_bindings_receivers' => [
                            'user',
                        ],
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
                    'to_bindings_receivers' => [
                        'user',
                    ],
                    'attachments' => null,
                ],
            ],
        ]);
    }

    public function testGetGenericAction()
    {
        $actionSettings = ActionSettings::factory([
            'settings' => [
                'subject' => 'the subject',
            ],
        ])->withEventActionType('send-email')->create();
        $user = User::factory()->hasConsumerAbility()->create();
        $response = $this->actingAs($user)->getJson('custom/action-settings/'.$actionSettings->id);
        $response->assertJson([
            'data' => [
                'settings' => [
                    'subject' => 'the subject',
                ],
                'id' => $actionSettings->id,
            ],
        ]);
    }

    public function testGetActionNotFound()
    {
        CustomActionModelResolver::register([], true);
        $user = User::factory()->hasConsumerAbility()->create();
        $response = $this->actingAs($user)->getJson('custom/manual-actions/send-email');
        $response->assertNotFound();
        $response->assertJson([
            'message' => 'not found',
        ]);
    }

    public function testGetActionSettingsForbidden()
    {
        $user = User::factory()->create();
        $this->actingAs($user)->getJson('custom/manual-actions/send-company-email')
            ->assertForbidden();
    }

    public function testUpdateGenericActionSettings()
    {
        $actionSettings = ActionSettings::factory([
            'settings' => [],
        ])->withEventActionType('send-email')->create();
        $newSettings = [
            'to_receivers' => [
                ['receiver_id' => User::factory()->create()->id, 'receiver_type' => 'user'],
            ],
        ];

        $user = User::factory()->hasConsumerAbility()->create();
        $response = $this->actingAs($user)->putJson("custom/action-settings/$actionSettings->id", [
            'settings' => $newSettings,
        ]);
        $response->assertOk();
        $response->assertJson([
            'data' => [
                'id' => $actionSettings->id,
                'settings' => $newSettings,
            ],
        ]);

        $this->assertEquals($newSettings, ActionSettings::findOrFail($actionSettings->id)->settings);
    }

    public function testUpdateManualActionSettings()
    {
        $actionSettings = ManualAction::factory([
            'type' => 'send-company-email',
        ])->sendMailRegistrationCompany()
            ->create()
            ->actionSettings;

        $newSettings = [
            'to_receivers' => [
                ['receiver_id' => User::factory()->create()->id, 'receiver_type' => 'user'],
            ],
            'test' => 'foo',
        ];

        $user = User::factory()->hasConsumerAbility()->create();
        $response = $this->actingAs($user)->putJson("custom/action-settings/$actionSettings->id", [
            'settings' => $newSettings,
        ]);
        $response->assertOk();
        $response->assertJson([
            'data' => [
                'id' => $actionSettings->id,
                'settings' => $newSettings,
            ],
        ]);

        $this->assertEquals($newSettings, ActionSettings::findOrFail($actionSettings->id)->settings);
    }

    public function testUpdateManualActionSettingsMissingRequired()
    {
        $actionSettings = ManualAction::factory([
            'type' => 'send-company-email',
        ])->sendMailRegistrationCompany()
            ->create()
            ->actionSettings;

        $newSettings = [
            'to_receivers' => [
                ['receiver_id' => User::factory()->create()->id, 'receiver_type' => 'user'],
            ],
        ];

        $user = User::factory()->hasConsumerAbility()->create();
        $this->actingAs($user)->putJson("custom/action-settings/$actionSettings->id", [
            'settings' => $newSettings,
        ])->assertUnprocessable()
            ->assertJson([
                'message' => 'The settings.test field is required.',
                'errors' => [
                    'settings.test' => [
                        'The settings.test field is required.',
                    ],
                ],
            ]);

    }

    public function testUpdateActionWithEventContextSettings()
    {
        $actionSettings = ActionSettings::factory([
            'settings' => [],
        ])->withEventActionType('send-email')->create();
        $newSettings = [
            'to_bindings_receivers' => ['user'],
        ];

        $user = User::factory()->hasConsumerAbility()->create();
        $response = $this->actingAs($user)->putJson("custom/action-settings/$actionSettings->id", [
            'settings' => $newSettings,
        ]);
        $response->assertOk();
        $response->assertJson([
            'data' => [
                'id' => $actionSettings->id,
                'settings' => $newSettings,
            ],
        ]);

        $this->assertEquals($newSettings, ActionSettings::findOrFail($actionSettings->id)->settings);
    }

    public function testUpdateActionSettingsForbidden()
    {
        $actionSettings = ActionSettings::factory([
            'settings' => [],
        ])->withEventActionType('send-email')->create();

        $user = User::factory()->create();
        $this->actingAs($user)->putJson("custom/action-settings/$actionSettings->id")
            ->assertForbidden();
    }

    /**
     * @dataProvider providerActionLocalizedSettings
     */
    public function testStoreActionLocalizedSettings($settingsContainerClass, $fromEventAction)
    {
        $resource = $settingsContainerClass == ActionSettings::class ? 'action-settings' : 'scoped-settings';
        $withActionType = $fromEventAction ? 'withEventActionType' : 'withManualActionType';

        /** @var ActionSettingsContainer $settingsContainer */
        $settingsContainer = $settingsContainerClass::factory([
            'settings' => [],
        ])->{$withActionType}('send-email')->create();
        $originalSettingsEn = [
            'subject' => 'original subject',
            'body' => 'original body',
        ];
        $originalSettingsFr = [
            'subject' => 'sujet original',
            'body' => 'corps original',
        ];
        $user = User::factory()->hasConsumerAbility()->create();

        // add en
        $response = $this->actingAs($user)->postJson("custom/{$resource}/{$settingsContainer->id}/localized-settings", [
            'locale' => 'en',
            'settings' => $originalSettingsEn,
        ]);
        $response->assertCreated();
        $this->assertEquals(1, $settingsContainer->localizedSettings()->count());
        $localizedSettingsEn = $settingsContainer->localizedSettings()->where('locale', 'en')->first();
        $response->assertJson([
            'data' => [
                'id' => $localizedSettingsEn->id,
                'locale' => 'en',
                'settings' => $originalSettingsEn,
            ],
        ]);
        $this->assertEquals($originalSettingsEn, $localizedSettingsEn->settings);

        // add fr
        $response = $this->actingAs($user)->postJson("custom/{$resource}/{$settingsContainer->id}/localized-settings", [
            'locale' => 'fr',
            'settings' => $originalSettingsFr,
        ]);
        $this->assertEquals(2, $settingsContainer->localizedSettings()->count());
        $localizedSettingsFr = $settingsContainer->localizedSettings()->where('locale', 'fr')->first();
        $response->assertJson([
            'data' => [
                'id' => $localizedSettingsFr->id,
                'locale' => 'fr',
                'settings' => $originalSettingsFr,
            ],
        ]);
        $this->assertEquals($originalSettingsFr, $localizedSettingsFr->settings);

        // get all
        $response = $this->actingAs($user)->getJson("custom/{$resource}/{$settingsContainer->id}/localized-settings");
        $response->assertJson([
            'data' => [
                ['id' => $localizedSettingsEn->id, 'locale' => 'en'],
                ['id' => $localizedSettingsFr->id, 'locale' => 'fr'],
            ],
        ]);

        // get en
        $response = $this->actingAs($user)->getJson("custom/localized-settings/{$localizedSettingsEn->id}");
        $response->assertJson([
            'data' => [
                'id' => $localizedSettingsEn->id,
                'locale' => 'en',
                'settings' => $originalSettingsEn,
            ],
        ]);
    }

    /**
     * @dataProvider providerActionLocalizedSettings
     */
    public function testStoreActionLocalizedSettingsMissingRequired($settingsContainerClass, $fromEventAction)
    {
        $resource = $settingsContainerClass == ActionSettings::class ? 'action-settings' : 'scoped-settings';
        $withActionType = $fromEventAction ? 'withEventActionType' : 'withManualActionType';

        /** @var ActionSettingsContainer $settingsContainer */
        $settingsContainer = $settingsContainerClass::factory([
            'settings' => [],
        ])->{$withActionType}('send-email')->create();
        $user = User::factory()->hasConsumerAbility()->create();

        $this->actingAs($user)->postJson("custom/{$resource}/{$settingsContainer->id}/localized-settings", [
            'locale' => 'en',
            'settings' => ['foo' => 'bar'],
        ])->assertUnprocessable()
            ->assertJson([
                'message' => 'The settings.subject field is required. (and 1 more error)',
                'errors' => [
                    'settings.subject' => [
                        'The settings.subject field is required.',
                    ],
                    'settings.body' => [
                        'The settings.body field is required.',
                    ],
                ],
            ]);
    }

    /**
     * @dataProvider providerActionLocalizedSettings
     */
    public function testStoreActionLocalizedSettingsWithLocalizedSettings($settingsContainerClass, $fromEventAction)
    {
        $resource = $settingsContainerClass == ActionSettings::class ? 'action-settings' : 'scoped-settings';
        $withActionType = $fromEventAction ? 'withEventActionType' : 'withManualActionType';

        /** @var ActionSettingsContainer $settingsContainer */
        $settingsContainer = $settingsContainerClass::factory()
            ->{$withActionType}('send-company-email')
            ->create();
        $originalSettingsEn = [
            'subject' => 'original subject',
            'body' => 'original body',
            'test_localized' => 'foo',
        ];
        $user = User::factory()->hasConsumerAbility()->create();

        // add en
        $response = $this->actingAs($user)->postJson("custom/{$resource}/{$settingsContainer->id}/localized-settings", [
            'locale' => 'en',
            'settings' => $originalSettingsEn,
        ]);
        $response->assertCreated();
        $this->assertEquals(1, $settingsContainer->localizedSettings()->count());
        $localizedSettingsEn = $settingsContainer->localizedSettings()->where('locale', 'en')->first();
        $response->assertJson([
            'data' => [
                'id' => $localizedSettingsEn->id,
                'locale' => 'en',
                'settings' => $originalSettingsEn,
            ],
        ]);
        $this->assertEquals($originalSettingsEn, $localizedSettingsEn->settings);
    }

    /**
     * @dataProvider providerActionLocalizedSettings
     */
    public function testStoreActionLocalizedSettingsForbidden($settingsContainerClass, $fromEventAction)
    {
        $resource = $settingsContainerClass == ActionSettings::class ? 'action-settings' : 'scoped-settings';
        $withActionType = $fromEventAction ? 'withEventActionType' : 'withManualActionType';

        /** @var ActionSettingsContainer $settingsContainer */
        $settingsContainer = $settingsContainerClass::factory()
            ->{$withActionType}('send-company-email')->create();

        $user = User::factory()->create();
        $this->actingAs($user)->postJson("custom/{$resource}/{$settingsContainer->id}/localized-settings")
            ->assertForbidden();
    }

    /**
     * @dataProvider providerActionLocalizedSettings
     */
    public function testUpdateActionLocalizedSettings($settingsContainerClass, $fromEventAction)
    {
        $withActionType = $fromEventAction ? 'withEventActionType' : 'withManualActionType';

        /** @var ActionSettingsContainer $settingsContainer */
        $settingsContainer = $settingsContainerClass::factory()
            ->{$withActionType}('send-email')
            ->create();

        $localizedSettings = new ActionLocalizedSettings;
        $localizedSettings->settings = [
            'subject' => 'original subject',
            'body' => 'original body',
        ];
        $localizedSettings->locale = 'en';
        $localizedSettings->localizable()->associate($settingsContainer);
        $localizedSettings->save();
        $updatedSettings = [
            'subject' => 'updated subject',
            'body' => 'updated body',
        ];
        $user = User::factory()->hasConsumerAbility()->create();
        $response = $this->actingAs($user)->putJson("custom/localized-settings/{$localizedSettings->id}", [
            'settings' => $updatedSettings,
            'locale' => 'es',
        ]);
        $response->assertOk();
        $response->assertJson([
            'data' => [
                'id' => $localizedSettings->id,
                'locale' => 'es',
                'settings' => $updatedSettings,
            ],
        ]);
        $this->assertEquals($updatedSettings, ActionLocalizedSettings::where('locale', 'es')->firstOrFail()->settings);
        $this->assertEquals(1, $settingsContainer->localizedSettings()->count());
        $this->assertEquals(1, ActionLocalizedSettings::count());
    }

    /**
     * @dataProvider providerActionLocalizedSettings
     */
    public function testUpdateActionLocalizedSettingsWithActionLocalizedSetting($settingsContainerClass, $fromEventAction)
    {
        $withActionType = $fromEventAction ? 'withEventActionType' : 'withManualActionType';

        /** @var ActionSettingsContainer $settingsContainer */
        $settingsContainer = $settingsContainerClass::factory()
            ->{$withActionType}('send-company-email')
            ->create();
        $localizedSettings = new ActionLocalizedSettings;
        $localizedSettings->settings = [
            'subject' => 'original subject',
            'body' => 'original body',
            'test_localized' => 'original test_localized',
        ];
        $localizedSettings->locale = 'en';
        $localizedSettings->localizable()->associate($settingsContainer);
        $localizedSettings->save();
        $updatedSettings = [
            'subject' => 'updated subject',
            'body' => 'updated body',
            'test_localized' => 'updated test_localized',
        ];
        $user = User::factory()->hasConsumerAbility()->create();
        $response = $this->actingAs($user)->putJson("custom/localized-settings/{$localizedSettings->id}", [
            'settings' => $updatedSettings,
            'locale' => 'es',
        ]);
        $response->assertOk();
        $response->assertJson([
            'data' => [
                'id' => $localizedSettings->id,
                'locale' => 'es',
                'settings' => $updatedSettings,
            ],
        ]);
        $this->assertEquals($updatedSettings, ActionLocalizedSettings::where('locale', 'es')->firstOrFail()->settings);
        $this->assertEquals(1, $settingsContainer->localizedSettings()->count());
        $this->assertEquals(1, ActionLocalizedSettings::count());
    }

    /**
     * @dataProvider providerActionLocalizedSettings
     */
    public function testUpdateActionLocalizedSettingsForbidden($settingsContainerClass, $fromEventAction)
    {
        $withActionType = $fromEventAction ? 'withEventActionType' : 'withManualActionType';

        /** @var ActionSettingsContainer $settingsContainer */
        $settingsContainer = $settingsContainerClass::factory()->{$withActionType}('send-email')->create();
        $localizedSettings = new ActionLocalizedSettings;
        $localizedSettings->settings = [
            'subject' => 'original subject',
            'body' => 'original body',
        ];
        $localizedSettings->locale = 'en';
        $localizedSettings->localizable()->associate($settingsContainer);
        $localizedSettings->save();

        $user = User::factory()->create();
        $this->actingAs($user)->putJson("custom/localized-settings/{$localizedSettings->id}")
            ->assertForbidden();
    }

    /**
     * @dataProvider providerActionLocalizedSettings
     */
    public function testDeleteActionLocalizedSettings($settingsContainerClass, $fromEventAction)
    {
        $withActionType = $fromEventAction ? 'withEventActionType' : 'withManualActionType';

        /** @var ActionSettingsContainer $settingsContainer */
        $settingsContainer = $settingsContainerClass::factory()->{$withActionType}('send-email')->create();
        $localizedSettings = new ActionLocalizedSettings;
        $localizedSettings->settings = [];
        $localizedSettings->locale = 'en';
        $localizedSettings->localizable()->associate($settingsContainer);
        $localizedSettings->save();

        $this->assertEquals(1, $settingsContainer->localizedSettings()->count());
        $this->assertEquals(1, ActionLocalizedSettings::count());

        $user = User::factory()->hasConsumerAbility()->create();
        $response = $this->actingAs($user)->delete("custom/localized-settings/$localizedSettings->id");
        $response->assertNoContent();
        $this->assertEquals(0, $settingsContainer->localizedSettings()->count());
        $this->assertEquals(0, ActionLocalizedSettings::count());
    }

    /**
     * @dataProvider providerActionLocalizedSettings
     */
    public function testDeleteActionLocalizedSettingsForbidden($settingsContainerClass, $fromEventAction)
    {
        $withActionType = $fromEventAction ? 'withEventActionType' : 'withManualActionType';

        /** @var ActionSettingsContainer $settingsContainer */
        $settingsContainer = $settingsContainerClass::factory()->$withActionType('send-email')->create();
        $localizedSettings = new ActionLocalizedSettings;
        $localizedSettings->settings = [];
        $localizedSettings->locale = 'en';
        $localizedSettings->localizable()->associate($settingsContainer);
        $localizedSettings->save();

        $user = User::factory()->create();
        $this->actingAs($user)->delete("custom/localized-settings/$localizedSettings->id")
            ->assertForbidden();
    }

    public static function providerActionLocalizedSettings()
    {
        return [
            [ActionSettings::class, true],
            [ActionSettings::class, false],
            [ActionScopedSettings::class, true],
            [ActionScopedSettings::class, false],
        ];
    }

    /**
     * @dataProvider providerActionScopedSettings
     */
    public function testStoreActionScopedSettings($fromEventAction)
    {
        $withActionType = $fromEventAction ? 'withEventActionType' : 'withManualActionType';

        /** @var ActionSettings $actionSettings */
        $actionSettings = ActionSettings::factory([
            'settings' => [],
        ])->{$withActionType}('send-email')->create();
        $settingsScope1 = [
            'to_receivers' => [
                ['receiver_id' => User::factory()->create()->id, 'receiver_type' => 'user'],
            ],
        ];
        $scope1 = ['company' => [
            'name' => 'my company scope 1',
        ]];
        $settingsScope2 = [
            'to_receivers' => [
                ['receiver_id' => User::factory()->create()->id, 'receiver_type' => 'user'],
            ],
        ];
        $scope2 = ['company' => [
            'name' => 'my company scope 2',
        ]];
        $user = User::factory()->hasConsumerAbility()->create();

        // add scope 1
        $response = $this->actingAs($user)->postJson("custom/action-settings/{$actionSettings->id}/scoped-settings", [
            'scope' => $scope1,
            'settings' => $settingsScope1,
            'name' => 'Scoped Settings 1',
        ]);
        $response->assertCreated();
        $this->assertEquals(1, $actionSettings->scopedSettings()->count());
        $scopedSettings1 = $actionSettings->scopedSettings()->where('name', 'Scoped Settings 1')->first();
        $response->assertJson([
            'data' => [
                'id' => $scopedSettings1->id,
                'scope' => $scope1,
                'settings' => $settingsScope1,
                'name' => 'Scoped Settings 1',
            ],
        ]);
        $this->assertEquals($settingsScope1, $scopedSettings1->settings);

        // add scope 2
        $response = $this->actingAs($user)->postJson("custom/action-settings/{$actionSettings->id}/scoped-settings", [
            'scope' => $scope2,
            'settings' => $settingsScope2,
            'name' => 'Scoped Settings 2',
        ]);
        $response->assertCreated();
        $this->assertEquals(2, $actionSettings->scopedSettings()->count());
        $scopedSettings2 = $actionSettings->scopedSettings()->where('name', 'Scoped Settings 2')->first();
        $response->assertJson([
            'data' => [
                'id' => $scopedSettings2->id,
                'scope' => $scope2,
                'settings' => $settingsScope2,
                'name' => 'Scoped Settings 2',
            ],
        ]);
        $this->assertEquals($settingsScope2, $scopedSettings2->settings);

        // get all
        $response = $this->actingAs($user)->getJson("custom/action-settings/{$actionSettings->id}/scoped-settings");
        $response->assertJson([
            'data' => [
                [
                    'id' => $scopedSettings1->id,
                    'name' => 'Scoped Settings 1',
                ],
                [
                    'id' => $scopedSettings2->id,
                    'name' => 'Scoped Settings 2',
                ],
            ],
        ]);

        // get scope 1
        $response = $this->actingAs($user)->getJson("custom/scoped-settings/{$scopedSettings1->id}");
        $response->assertOk();
        $response->assertJson([
            'data' => [
                'id' => $scopedSettings1->id,
                'scope' => $scope1,
                'settings' => $settingsScope1,
                'name' => 'Scoped Settings 1',
            ],
        ]);
    }

    public function testListScopedActionsWithFilter()
    {
        /** @var ActionSettings $actionSettings */
        $actionSettings = ActionSettings::factory()->create();
        $scopedSettings = ActionScopedSettings::factory([
            'name' => 'my one',
        ])->for($actionSettings, 'actionSettings')->create();
        ActionScopedSettings::factory([
            'name' => 'my two',
        ])->for($actionSettings, 'actionSettings')->create();

        $user = User::factory()->hasConsumerAbility()->create();
        $params = http_build_query(['name' => 'one']);
        $this->actingAs($user)->getJson("custom/action-settings/$actionSettings->id/scoped-settings?$params")
            ->assertJsonCount(1, 'data')
            ->assertJson([
                'data' => [
                    [
                        'id' => $scopedSettings->id,
                        'name' => 'my one',
                    ],
                ],
            ]);
    }

    public function testStoreActionScopedWithEventContextSettings()
    {
        /** @var ActionSettings $actionSettings */
        $actionSettings = ActionSettings::factory([
            'settings' => [],
        ])->withEventActionType('send-email')->create();
        $settingsScope1 = [
            'to_bindings_receivers' => ['user'],
        ];
        $scope1 = ['company' => [
            'name' => 'my company scope 1',
        ]];
        $user = User::factory()->hasConsumerAbility()->create();

        // add scope
        $response = $this->actingAs($user)->postJson("custom/action-settings/{$actionSettings->id}/scoped-settings", [
            'scope' => $scope1,
            'settings' => $settingsScope1,
            'name' => 'my scoped stettings name',
        ]);
        $response->assertCreated();
        $this->assertEquals(1, $actionSettings->scopedSettings()->count());
        $scopedSettings1 = $actionSettings->scopedSettings()->where('scope', 'like', '%my company scope 1%')->first();
        $response->assertJson([
            'data' => [
                'id' => $scopedSettings1->id,
                'scope' => $scope1,
                'settings' => $settingsScope1,
                'name' => 'my scoped stettings name',
            ],
        ]);
        $this->assertEquals($settingsScope1, $scopedSettings1->settings);
    }

    /**
     * @dataProvider providerActionScopedSettings
     */
    public function testStoreActionScopedSettingsForbidden($fromEventAction)
    {
        $withActionType = $fromEventAction ? 'withEventActionType' : 'withManualActionType';

        /** @var ActionSettings $actionSettings */
        $actionSettings = ActionSettings::factory()->{$withActionType}('send-email')->create();

        $user = User::factory()->create();
        $this->actingAs($user)->postJson("custom/action-settings/{$actionSettings->id}/scoped-settings")
            ->assertForbidden();
    }

    /**
     * @dataProvider providerActionScopedSettings
     */
    public function testUpdateActionScopedSettingsSuccess($fromEventAction)
    {
        $withActionType = $fromEventAction ? 'withEventActionType' : 'withManualActionType';

        /** @var ActionSettings $actionSettings */
        $actionSettings = ActionSettings::factory()->{$withActionType}('send-email')->create();
        $scopedSettings = ActionScopedSettings::factory([
            'settings' => [
                'to_receivers' => [
                    ['receiver_id' => 789, 'receiver_type' => 'user'],
                ],
            ],
            'scope' => [
                'company' => [
                    'name' => 'my company scope 1',
                ],
            ],
        ])->for($actionSettings, 'actionSettings')
            ->create();

        $updatedSettings = [
            'to_receivers' => [
                ['receiver_id' => User::factory()->create()->id, 'receiver_type' => 'user'],
            ],
        ];
        $updatedScope = ['company' => [
            'name' => 'my company scope 2',
        ]];
        $user = User::factory()->hasConsumerAbility()->create();
        $response = $this->actingAs($user)->putJson("custom/scoped-settings/{$scopedSettings->id}", [
            'settings' => $updatedSettings,
            'scope' => $updatedScope,
            'name' => 'updated name',
        ]);
        $response->assertOk();
        $response->assertJson([
            'data' => [
                'id' => $scopedSettings->id,
                'scope' => $updatedScope,
                'settings' => $updatedSettings,
                'name' => 'updated name',
            ],
        ]);
        $storedScopedSettings = ActionScopedSettings::findOrFail($scopedSettings->id);
        $this->assertEquals($updatedSettings, $storedScopedSettings->settings);
        $this->assertEquals($updatedScope, $storedScopedSettings->scope);
        $this->assertEquals(1, $actionSettings->scopedSettings()->count());
        $this->assertEquals(1, ActionScopedSettings::count());
    }

    public function testUpdateActionScopedWithEventContextSettings()
    {
        /** @var ActionSettings $actionSettings */
        $actionSettings = EventAction::factory()
            ->sendMailRegistrationCompany()
            ->create()
            ->actionSettings;

        $scopedSettings = ActionScopedSettings::factory([
            'settings' => [
                'to_receivers' => [
                    ['receiver_id' => 789, 'receiver_type' => 'user'],
                ],
            ],
            'scope' => [
                'company' => [
                    'name' => 'my company scope 1',
                ],
            ],
        ])->for($actionSettings, 'actionSettings')
            ->create();

        $settingsScope1 = [
            'to_bindings_receivers' => ['user'],
            'to_bindings_emails' => ['responsibles.*.email'],
        ];
        $scope1 = ['company' => [
            'name' => 'my company scope 1',
        ]];
        $user = User::factory()->hasConsumerAbility()->create();

        // add scope
        $response = $this->actingAs($user)->putJson("custom/scoped-settings/{$scopedSettings->id}", [
            'scope' => $scope1,
            'settings' => $settingsScope1,
            'name' => 'updated name',
        ]);
        $response->assertOk();
        $this->assertEquals(1, $actionSettings->scopedSettings()->count());
        $scopedSettings1 = $actionSettings->scopedSettings()->where('scope', 'like', '%my company scope 1%')->first();
        $response->assertJson([
            'data' => [
                'id' => $scopedSettings1->id,
                'scope' => $scope1,
                'settings' => $settingsScope1,
                'name' => 'updated name',
            ],
        ]);
        $this->assertEquals($settingsScope1, $scopedSettings1->settings);
    }

    /**
     * @dataProvider providerActionScopedSettings
     */
    public function testUpdateActionScopedSettingsForbidden($fromEventAction)
    {
        $withActionType = $fromEventAction ? 'withEventActionType' : 'withManualActionType';

        $scopedSettings = ActionScopedSettings::factory()->{$withActionType}('send-email')->create();

        $user = User::factory()->create();
        $this->actingAs($user)->putJson("custom/scoped-settings/{$scopedSettings->id}")
            ->assertForbidden();
    }

    /**
     * @dataProvider providerActionScopedSettings
     */
    public function testDeleteActionScopedSettings($fromEventAction)
    {
        $withActionType = $fromEventAction ? 'withEventActionType' : 'withManualActionType';

        /** @var ActionSettings $actionSettings */
        $actionSettings = ActionSettings::factory()->{$withActionType}('send-email')->create();

        $scopedSettings = ActionScopedSettings::factory([
            'settings' => [],
            'scope' => [],
        ])->for($actionSettings, 'actionSettings')
            ->create();

        $this->assertEquals(1, $actionSettings->scopedSettings()->count());
        $this->assertEquals(1, ActionScopedSettings::count());

        $user = User::factory()->hasConsumerAbility()->create();
        $response = $this->actingAs($user)->delete("custom/scoped-settings/$scopedSettings->id");
        $response->assertNoContent();
        $this->assertEquals(0, $actionSettings->scopedSettings()->count());
        $this->assertEquals(0, ActionScopedSettings::count());
    }

    /**
     * @dataProvider providerActionScopedSettings
     */
    public function testDeleteActionScopedSettingsForbidden($fromEventAction)
    {
        $withActionType = $fromEventAction ? 'withEventActionType' : 'withManualActionType';

        /** @var ActionSettings $actionSettings */
        $scopedSettings = ActionScopedSettings::factory()->{$withActionType}('send-email')->create();

        $user = User::factory()->create();
        $this->actingAs($user)->delete("custom/scoped-settings/$scopedSettings->id")
            ->assertForbidden();
    }

    public static function providerActionScopedSettings()
    {
        return [
            [true],
            [false],
        ];
    }

    /**
     * @dataProvider providerSettingsValidation
     */
    public function testSettingsValidation($settings, $success)
    {
        $actionSettings = ActionSettings::factory([
            'settings' => [],
        ])->withEventActionType('send-email')->create();

        $user = User::factory()->hasConsumerAbility()->create();
        $response = $this->actingAs($user)->postJson("custom/action-settings/{$actionSettings->id}/localized-settings", [
            'locale' => 'en',
            'settings' => $settings,
        ]);
        $response->assertStatus($success ? 201 : 422);
    }

    public static function providerSettingsValidation()
    {
        return [
            [
                [
                    'subject' => 'original {{ to.name ? "true" : "false" }} subject',
                    'body' => 'original {{ to.name ? "true" : "false" }} body {{ to.name ? "true" : "false" }}',
                ],
                true,
            ],
            [
                [
                    'subject' => 'original {{ "true }} subject',
                    'body' => 'original subject',
                ],
                false,
            ],
            [
                [
                    'subject' => 'original subject',
                    'body' => 'original {{ "true }} subject',
                ],
                false,
            ],
        ];
    }
}
