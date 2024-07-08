<?php

namespace Tests\Feature;

use App\Actions\SendCompanyRegistrationMail;
use App\Models\Company;
use App\Models\User;
use App\Models\UserWithoutPreference;
use Comhon\CustomAction\Facades\CustomActionModelResolver;
use Comhon\CustomAction\Files\SystemFile;
use Comhon\CustomAction\Mail\Custom;
use Comhon\CustomAction\Models\ActionLocalizedSettings;
use Comhon\CustomAction\Models\ActionScopedSettings;
use Comhon\CustomAction\Models\ActionSettingsContainer;
use Comhon\CustomAction\Models\CustomActionSettings;
use Comhon\CustomAction\Models\CustomEventListener;
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

    private function getActionInstance(): SendCompanyRegistrationMail
    {
        return app(SendCompanyRegistrationMail::class);
    }

    /**
     * @dataProvider providerHandleUniqueActionSuccess
     */
    public function testHandleUniqueActionSuccess($preferredLocale, $appLocale, $fallbackLocale, $success)
    {
        App::setLocale($appLocale);
        App::setFallbackLocale($fallbackLocale);
        $user = User::factory(null, ['preferred_locale' => $preferredLocale])->create();
        $company = Company::factory()->create();
        CustomActionSettings::factory()->sendMailRegistrationCompany(null, false, 'send-company-email', true)->create();

        $bindings = ['company' => $company, 'logo' => new SystemFile($this->getAssetPath())];

        Mail::fake();

        if (! $success) {
            $this->expectExceptionMessage('localized mail values not found');
        }
        $this->getActionInstance()->handle($bindings, $user);

        $mails = [];
        Mail::assertSent(Custom::class, 1);
        Mail::assertSent(Custom::class, function (Custom $mail) use (&$mails) {
            $mails[] = $mail;

            return true;
        });
        $mails[0]->assertHasTo($user->email);
        $mails[0]->assertHasSubject(
            "Dear $user->first_name, company $company->name (login: December 12, 2022 at 12:00 AM (UTC) December 12, 2022 at 12:00 AM (UTC))"
        );
        $this->assertTrue($mails[0]->hasAttachment(Attachment::fromPath($this->getAssetPath())));
    }

    public static function providerHandleUniqueActionSuccess()
    {
        return [
            ['en', 'fr', 'fr', true],
            ['es', 'en', 'fr', true],
            ['es', 'es', 'en', true],
            ['es', 'es', 'es', false],
        ];
    }

    /**
     * @dataProvider providerHandleUniqueActionUserWithoutPreferencesSuccess
     */
    public function testHandleUniqueActionUserWithoutPreferencesSuccess($appLocale, $fallbackLocale, $success)
    {
        App::setLocale($appLocale);
        App::setFallbackLocale($fallbackLocale);
        $user = UserWithoutPreference::factory()->create();
        $company = Company::factory()->create();
        CustomActionSettings::factory()->sendMailRegistrationCompany(null, false, 'send-company-email', true)->create();

        $bindings = ['company' => $company, 'logo' => new SystemFile($this->getAssetPath())];

        Mail::fake();

        if (! $success) {
            $this->expectExceptionMessage('localized mail values not found');
        }
        $this->getActionInstance()->handle($bindings, $user);

        $mails = [];
        Mail::assertSent(Custom::class, 1);
        Mail::assertSent(Custom::class, function (Custom $mail) use (&$mails) {
            $mails[] = $mail;

            return true;
        });
        $mails[0]->assertHasTo($user->email);
        $mails[0]->assertHasSubject(
            "Dear $user->first_name, company $company->name (login: December 12, 2022 at 12:00 AM (UTC) December 12, 2022 at 12:00 AM (UTC))"
        );
        $this->assertTrue($mails[0]->hasAttachment(Attachment::fromPath($this->getAssetPath())));
    }

    public static function providerHandleUniqueActionUserWithoutPreferencesSuccess()
    {
        return [
            ['en', 'fr', true],
            ['es', 'en', true],
            ['es', 'es', false],
        ];
    }

    public function testHandleUniqueActionWithoutSettings()
    {
        $user = User::factory()->create();
        $company = Company::factory()->create();

        $bindings = ['company' => $company, 'logo' => new SystemFile($this->getAssetPath())];
        $this->expectExceptionMessage('action settings not set for App\Actions\SendCompanyRegistrationMail');
        $this->getActionInstance()->handle($bindings, $user);
    }

    public function testHandleUniqueActionDuplicated()
    {
        $user = User::factory()->create();
        $company = Company::factory()->create();

        // create two unique actions
        CustomActionSettings::factory()->sendMailRegistrationCompany(null, false, 'send-company-email', true)->create();
        CustomActionSettings::factory()->sendMailRegistrationCompany(null, false, 'send-company-email', true)->create();

        $bindings = ['company' => $company, 'logo' => new SystemFile($this->getAssetPath())];
        $this->expectExceptionMessage("several 'send-company-email' actions found");
        $this->getActionInstance()->handle($bindings, $user);
    }

    public function testGetActionsSuccess()
    {
        config(['custom-action.unique_actions' => [SendCompanyRegistrationMail::class]]);
        $user = User::factory()->hasConsumerAbility()->create();
        $response = $this->actingAs($user)->getJson('custom/unique-actions');
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
        $this->actingAs($user)->getJson('custom/unique-actions')
            ->assertForbidden();
    }

    public function testActionShemaSuccess()
    {
        $user = User::factory()->hasConsumerAbility()->create();

        $response = $this->actingAs($user)->getJson('custom/actions/send-email/schema');
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
                    'subject' => RuleHelper::getRuleName('text_template'),
                    'body' => RuleHelper::getRuleName('html_template'),
                ],
                'unique' => false,
            ],
        ]);

        $response = $this->actingAs($user)->getJson('custom/actions/send-company-email/schema');
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
                    'test' => 'string',
                ],
                'localized_settings_schema' => [
                    'subject' => RuleHelper::getRuleName('text_template'),
                    'body' => RuleHelper::getRuleName('html_template'),
                    'test_localized' => 'string',
                ],
                'unique' => true,
            ],
        ]);
    }

    public function testActionShemaWithContextSuccess()
    {
        $user = User::factory()->hasConsumerAbility()->create();

        $params = http_build_query(['event_context' => 'company-registered']);
        $this->actingAs($user)->getJson("custom/actions/send-email/schema?$params")
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
                        'subject' => RuleHelper::getRuleName('text_template'),
                        'body' => RuleHelper::getRuleName('html_template'),
                    ],
                    'unique' => false,
                ],
            ]);
    }

    public function testActionShemaWithInvalidContext()
    {
        $user = User::factory()->hasConsumerAbility()->create();

        $params = http_build_query(['event_context' => 'stored-file']);
        $this->actingAs($user)->getJson("custom/actions/send-email/schema?$params")
            ->assertUnprocessable()
            ->assertJson([
                'message' => 'the event context is not subclass of custom-event',
            ]);
    }

    public function testActionShemaWithInvalidContext2()
    {
        $user = User::factory()->hasConsumerAbility()->create();

        $params = http_build_query(['event_context' => 'custom-event']);
        $this->actingAs($user)->getJson("custom/actions/send-email/schema?$params")
            ->assertUnprocessable()
            ->assertJson([
                'message' => 'the event context is not subclass of custom-event',
            ]);
    }

    public function testActionShemaNotFound()
    {
        CustomActionModelResolver::register([], true);
        $user = User::factory()->hasConsumerAbility()->create();

        $response = $this->actingAs($user)->getJson('custom/actions/send-email/schema');
        $response->assertNotFound();
    }

    public function testActionShemaForbidden()
    {
        $user = User::factory()->create();
        $this->actingAs($user)->getJson('custom/actions/send-email/schema')
            ->assertForbidden();
    }

    public function testGetUniqueActionNotCreated()
    {
        $user = User::factory()->hasConsumerAbility()->create();
        $response = $this->actingAs($user)->getJson('custom/action-settings/send-company-email');
        $response->assertJson([
            'data' => [
                'type' => 'send-company-email',
                'settings' => [],
            ],
        ]);
    }

    public function testGetUniqueActionCreated()
    {
        $customActionSettings = CustomActionSettings::factory(null, [
            'type' => 'send-company-email',
            'settings' => [
                'subject' => 'the subject',
            ],
        ])->create();
        $user = User::factory()->hasConsumerAbility()->create();

        $response = $this->actingAs($user)->getJson('custom/action-settings/send-company-email');
        $response->assertJson([
            'data' => [
                'id' => $customActionSettings->id,
                'type' => 'send-company-email',
                'settings' => [
                    'subject' => 'the subject',
                ],
            ],
        ]);

        $response = $this->actingAs($user)->getJson('custom/action-settings/'.$customActionSettings->id);
        $response->assertJson([
            'data' => [
                'id' => $customActionSettings->id,
                'type' => 'send-company-email',
                'settings' => [
                    'subject' => 'the subject',
                ],
            ],
        ]);
    }

    public function testGetUniqueActionWithSeveralSettings()
    {
        CustomActionSettings::factory(null, [
            'type' => 'send-company-email',
            'settings' => [],
        ])->create();
        CustomActionSettings::factory(null, [
            'type' => 'send-company-email',
            'settings' => [],
        ])->create();
        $user = User::factory()->hasConsumerAbility()->create();

        $response = $this->actingAs($user)->getJson('custom/action-settings/send-company-email');
        $response->assertServerError();
    }

    public function testGetGenericAction()
    {
        $customActionSettings = CustomActionSettings::factory(null, [
            'type' => 'send-email',
            'settings' => [
                'subject' => 'the subject',
            ],
        ])->create();
        $user = User::factory()->hasConsumerAbility()->create();
        $response = $this->actingAs($user)->getJson('custom/action-settings/'.$customActionSettings->id);
        $response->assertJson([
            'data' => [
                'type' => 'send-email',
                'settings' => [],
                'id' => $customActionSettings->id,
            ],
        ]);
    }

    public function testGetActionNotFound()
    {
        CustomActionModelResolver::register([], true);
        $user = User::factory()->hasConsumerAbility()->create();
        $response = $this->actingAs($user)->getJson('custom/action-settings/send-email');
        $response->assertNotFound();
        $response->assertJson([
            'message' => 'not found',
        ]);
    }

    public function testGetGenericActionWithKey()
    {
        $user = User::factory()->hasConsumerAbility()->create();
        $response = $this->actingAs($user)->getJson('custom/action-settings/send-email');
        $response->assertJson([
            'message' => 'action must be a unique action',
        ]);
    }

    public function testGetActionSettingsForbidden()
    {
        $user = User::factory()->create();
        $this->actingAs($user)->getJson('custom/action-settings/send-company-email')
            ->assertForbidden();
    }

    public function testUpdateGenericActionSettings()
    {
        $customActionSettings = CustomActionSettings::factory(null, [
            'type' => 'send-email',
            'settings' => [],
        ])->create();
        $newSettings = [
            'to_receivers' => [
                ['receiver_id' => User::factory()->create()->id, 'receiver_type' => 'user'],
            ],
        ];

        $user = User::factory()->hasConsumerAbility()->create();
        $response = $this->actingAs($user)->putJson("custom/action-settings/$customActionSettings->id", [
            'settings' => $newSettings,
        ]);
        $response->assertOk();
        $response->assertJson([
            'data' => [
                'id' => $customActionSettings->id,
                'type' => 'send-email',
                'settings' => $newSettings,
            ],
        ]);

        $this->assertEquals($newSettings, CustomActionSettings::findOrFail($customActionSettings->id)->settings);
    }

    public function testUpdateUniqueActionSettings()
    {
        $customActionSettings = CustomActionSettings::factory(null, [
            'type' => 'send-company-email',
            'settings' => [],
        ])->create();
        $newSettings = [
            'to_receivers' => [
                ['receiver_id' => User::factory()->create()->id, 'receiver_type' => 'user'],
            ],
        ];

        $user = User::factory()->hasConsumerAbility()->create();
        $response = $this->actingAs($user)->putJson("custom/action-settings/$customActionSettings->id", [
            'settings' => $newSettings,
        ]);
        $response->assertOk();
        $response->assertJson([
            'data' => [
                'id' => $customActionSettings->id,
                'type' => 'send-company-email',
                'settings' => $newSettings,
            ],
        ]);

        $this->assertEquals($newSettings, CustomActionSettings::findOrFail($customActionSettings->id)->settings);
    }

    public function testUpdateActionWithEventContextSettings()
    {
        $customActionSettings = CustomActionSettings::factory(null, [
            'type' => 'send-email',
            'settings' => [],
        ])->hasAttached(CustomEventListener::factory(), [], 'eventListeners')->create();
        $newSettings = [
            'to_bindings_receivers' => ['user'],
        ];

        $user = User::factory()->hasConsumerAbility()->create();
        $response = $this->actingAs($user)->putJson("custom/action-settings/$customActionSettings->id", [
            'settings' => $newSettings,
        ]);
        $response->assertOk();
        $response->assertJson([
            'data' => [
                'id' => $customActionSettings->id,
                'type' => 'send-email',
                'settings' => $newSettings,
            ],
        ]);

        $this->assertEquals($newSettings, CustomActionSettings::findOrFail($customActionSettings->id)->settings);
    }

    public function testUpdateActionSettingsForbidden()
    {
        $customActionSettings = CustomActionSettings::factory(null, [
            'type' => 'send-email',
            'settings' => [],
        ])->create();

        $user = User::factory()->create();
        $this->actingAs($user)->putJson("custom/action-settings/$customActionSettings->id")
            ->assertForbidden();
    }

    /**
     * @dataProvider providerActionLocalizedSettings
     *
     * @return void
     */
    public function testStoreActionLocalizedSettings($settingsContainerClass)
    {
        $resource = $settingsContainerClass == CustomActionSettings::class ? 'action-settings' : 'scoped-settings';

        /** @var ActionSettingsContainer $settingsContainer */
        $settingsContainer = $settingsContainerClass::factory(null, [
            'settings' => [],
        ])->create();
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
     *
     * @return void
     */
    public function testStoreActionLocalizedSettingsWithEventContext($settingsContainerClass)
    {
        $resource = $settingsContainerClass == CustomActionSettings::class ? 'action-settings' : 'scoped-settings';

        /** @var ActionSettingsContainer $settingsContainer */
        $settingsContainer = $settingsContainerClass::factory()
            ->actionType('send-company-email')
            ->create();
        $originalSettingsEn = [
            'subject' => 'original subject',
            'body' => 'original body',
            'test_localized' => 'foo',
        ];
        $customActionSettings = $settingsContainer instanceof ActionScopedSettings
            ? $settingsContainer->customActionSettings
            : $settingsContainer;
        $customActionSettings->eventListeners()->attach(CustomEventListener::factory()->create());
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
     *
     * @return void
     */
    public function testStoreActionLocalizedSettingsForbidden($settingsContainerClass)
    {
        $resource = $settingsContainerClass == CustomActionSettings::class ? 'action-settings' : 'scoped-settings';

        /** @var ActionSettingsContainer $settingsContainer */
        $settingsContainer = $settingsContainerClass::factory(null, [
            'settings' => [],
        ])->create();

        $user = User::factory()->create();
        $this->actingAs($user)->postJson("custom/{$resource}/{$settingsContainer->id}/localized-settings")
            ->assertForbidden();
    }

    /**
     * @dataProvider providerActionLocalizedSettings
     *
     * @return void
     */
    public function testUpdateActionLocalizedSettings($settingsContainerClass)
    {
        /** @var ActionSettingsContainer $settingsContainer */
        $settingsContainer = $settingsContainerClass::factory()->create();
        $localizedSettings = new ActionLocalizedSettings();
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
     *
     * @return void
     */
    public function testUpdateActionLocalizedSettingsWithEventContext($settingsContainerClass)
    {
        /** @var ActionSettingsContainer $settingsContainer */
        $settingsContainer = $settingsContainerClass::factory()
            ->actionType('send-company-email')
            ->create();
        $localizedSettings = new ActionLocalizedSettings();
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
     *
     * @return void
     */
    public function testUpdateActionLocalizedSettingsForbidden($settingsContainerClass)
    {
        /** @var ActionSettingsContainer $settingsContainer */
        $settingsContainer = $settingsContainerClass::factory()->create();
        $localizedSettings = new ActionLocalizedSettings();
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
     *
     * @return void
     */
    public function testDeleteActionLocalizedSettings($settingsContainerClass)
    {
        /** @var ActionSettingsContainer $settingsContainer */
        $settingsContainer = $settingsContainerClass::factory()->create();
        $localizedSettings = new ActionLocalizedSettings();
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
     *
     * @return void
     */
    public function testDeleteActionLocalizedSettingsForbidden($settingsContainerClass)
    {
        /** @var ActionSettingsContainer $settingsContainer */
        $settingsContainer = $settingsContainerClass::factory()->create();
        $localizedSettings = new ActionLocalizedSettings();
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
            [CustomActionSettings::class],
            [ActionScopedSettings::class],
        ];
    }

    /**
     * @return void
     */
    public function testStoreActionScopedSettings()
    {
        /** @var CustomActionSettings $customActionSettings */
        $customActionSettings = CustomActionSettings::factory(null, [
            'settings' => [],
        ])->create();
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
        $response = $this->actingAs($user)->postJson("custom/action-settings/{$customActionSettings->id}/scoped-settings", [
            'scope' => $scope1,
            'settings' => $settingsScope1,
        ]);
        $response->assertCreated();
        $this->assertEquals(1, $customActionSettings->scopedSettings()->count());
        $scopedSettings1 = $customActionSettings->scopedSettings()->where('scope', 'like', '%my company scope 1%')->first();
        $response->assertJson([
            'data' => [
                'id' => $scopedSettings1->id,
                'scope' => $scope1,
                'settings' => $settingsScope1,
            ],
        ]);
        $this->assertEquals($settingsScope1, $scopedSettings1->settings);

        // add scope 2
        $response = $this->actingAs($user)->postJson("custom/action-settings/{$customActionSettings->id}/scoped-settings", [
            'scope' => $scope2,
            'settings' => $settingsScope2,
        ]);
        $response->assertCreated();
        $this->assertEquals(2, $customActionSettings->scopedSettings()->count());
        $scopedSettings2 = $customActionSettings->scopedSettings()->where('scope', 'like', '%my company scope 2%')->first();
        $response->assertJson([
            'data' => [
                'id' => $scopedSettings2->id,
                'scope' => $scope2,
                'settings' => $settingsScope2,
            ],
        ]);
        $this->assertEquals($settingsScope2, $scopedSettings2->settings);

        // get all
        $response = $this->actingAs($user)->getJson("custom/action-settings/{$customActionSettings->id}/scoped-settings");
        $response->assertJson([
            'data' => [
                $scopedSettings1->id,
                $scopedSettings2->id,
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
            ],
        ]);
    }

    public function testStoreActionScopedWithEventContextSettings()
    {
        /** @var CustomActionSettings $customActionSettings */
        $customActionSettings = CustomActionSettings::factory(null, [
            'settings' => [],
        ])->hasAttached(CustomEventListener::factory(), [], 'eventListeners')->create();
        $settingsScope1 = [
            'to_bindings_receivers' => ['user'],
        ];
        $scope1 = ['company' => [
            'name' => 'my company scope 1',
        ]];
        $user = User::factory()->hasConsumerAbility()->create();

        // add scope
        $response = $this->actingAs($user)->postJson("custom/action-settings/{$customActionSettings->id}/scoped-settings", [
            'scope' => $scope1,
            'settings' => $settingsScope1,
        ]);
        $response->assertCreated();
        $this->assertEquals(1, $customActionSettings->scopedSettings()->count());
        $scopedSettings1 = $customActionSettings->scopedSettings()->where('scope', 'like', '%my company scope 1%')->first();
        $response->assertJson([
            'data' => [
                'id' => $scopedSettings1->id,
                'scope' => $scope1,
                'settings' => $settingsScope1,
            ],
        ]);
        $this->assertEquals($settingsScope1, $scopedSettings1->settings);
    }

    /**
     * @return void
     */
    public function testStoreActionScopedSettingsForbidden()
    {
        /** @var CustomActionSettings $customActionSettings */
        $customActionSettings = CustomActionSettings::factory(null, [
            'settings' => [],
        ])->create();

        $user = User::factory()->create();
        $this->actingAs($user)->postJson("custom/action-settings/{$customActionSettings->id}/scoped-settings")
            ->assertForbidden();
    }

    public function testUpdateActionScopedSettings()
    {
        /** @var CustomActionSettings $customActionSettings */
        $customActionSettings = CustomActionSettings::factory()->create();
        $scopedSettings = new ActionScopedSettings();
        $scopedSettings->settings = [
            'to_receivers' => [
                ['receiver_id' => 789, 'receiver_type' => 'user'],
            ],
        ];
        $scopedSettings->scope = ['company' => [
            'name' => 'my company scope 1',
        ]];
        $scopedSettings->customActionSettings()->associate($customActionSettings);
        $scopedSettings->save();

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
        ]);
        $response->assertOk();
        $response->assertJson([
            'data' => [
                'id' => $scopedSettings->id,
                'scope' => $updatedScope,
                'settings' => $updatedSettings,
            ],
        ]);
        $storedScopedSettings = ActionScopedSettings::findOrFail($scopedSettings->id);
        $this->assertEquals($updatedSettings, $storedScopedSettings->settings);
        $this->assertEquals($updatedScope, $storedScopedSettings->scope);
        $this->assertEquals(1, $customActionSettings->scopedSettings()->count());
        $this->assertEquals(1, ActionScopedSettings::count());
    }

    public function testUpdateActionScopedWithEventContextSettings()
    {
        /** @var CustomActionSettings $customActionSettings */
        $customActionSettings = CustomActionSettings::factory()
            ->hasAttached(CustomEventListener::factory(), [], 'eventListeners')
            ->create();
        $scopedSettings = new ActionScopedSettings();
        $scopedSettings->settings = [
            'to_receivers' => [
                ['receiver_id' => 789, 'receiver_type' => 'user'],
            ],
        ];
        $scopedSettings->scope = ['company' => [
            'name' => 'my company scope 1',
        ]];
        $scopedSettings->customActionSettings()->associate($customActionSettings);
        $scopedSettings->save();

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
        ]);
        $response->assertOk();
        $this->assertEquals(1, $customActionSettings->scopedSettings()->count());
        $scopedSettings1 = $customActionSettings->scopedSettings()->where('scope', 'like', '%my company scope 1%')->first();
        $response->assertJson([
            'data' => [
                'id' => $scopedSettings1->id,
                'scope' => $scope1,
                'settings' => $settingsScope1,
            ],
        ]);
        $this->assertEquals($settingsScope1, $scopedSettings1->settings);
    }

    public function testUpdateActionScopedSettingsForbidden()
    {
        $scopedSettings = ActionScopedSettings::factory()->create();

        $user = User::factory()->create();
        $this->actingAs($user)->putJson("custom/scoped-settings/{$scopedSettings->id}")
            ->assertForbidden();
    }

    public function testDeleteActionScopedSettings()
    {
        /** @var CustomActionSettings $customActionSettings */
        $customActionSettings = CustomActionSettings::factory()->create();
        $scopedSettings = new ActionScopedSettings();
        $scopedSettings->settings = [];
        $scopedSettings->scope = [];
        $scopedSettings->customActionSettings()->associate($customActionSettings);
        $scopedSettings->save();

        $this->assertEquals(1, $customActionSettings->scopedSettings()->count());
        $this->assertEquals(1, ActionScopedSettings::count());

        $user = User::factory()->hasConsumerAbility()->create();
        $response = $this->actingAs($user)->delete("custom/scoped-settings/$scopedSettings->id");
        $response->assertNoContent();
        $this->assertEquals(0, $customActionSettings->scopedSettings()->count());
        $this->assertEquals(0, ActionScopedSettings::count());
    }

    public function testDeleteActionScopedSettingsForbidden()
    {
        /** @var CustomActionSettings $customActionSettings */
        $scopedSettings = ActionScopedSettings::factory()->create();

        $user = User::factory()->create();
        $this->actingAs($user)->delete("custom/scoped-settings/$scopedSettings->id")
            ->assertForbidden();
    }

    /**
     * @dataProvider providerSettingsValidation
     *
     * @return void
     */
    public function testSettingsValidation($settings, $success)
    {
        $customActionSettings = CustomActionSettings::factory(null, [
            'settings' => [],
        ])->create();

        $user = User::factory()->hasConsumerAbility()->create();
        $response = $this->actingAs($user)->postJson("custom/action-settings/{$customActionSettings->id}/localized-settings", [
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
