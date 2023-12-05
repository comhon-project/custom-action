<?php

namespace Tests\Feature;

use Comhon\CustomAction\Models\ActionSettingsContainer;
use Comhon\CustomAction\Models\ActionLocalizedSettings;
use Comhon\CustomAction\Models\ActionScopedSettings;
use Comhon\CustomAction\Models\CustomActionSettings;
use Comhon\CustomAction\Actions\SendTemplatedMail;
use Comhon\CustomAction\Mail\Custom;
use Comhon\CustomAction\Resolver\ModelResolverContainer;
use Comhon\CustomAction\Tests\Support\Models\Company;
use Comhon\CustomAction\Tests\Support\CompanyRegistered;
use Comhon\CustomAction\Tests\Support\SendCompanyRegistrationMail;
use Comhon\CustomAction\Tests\Support\Models\User;
use Comhon\CustomAction\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;

class CustomActionTest extends TestCase
{
    use RefreshDatabase;

    private static $asset = __DIR__ 
        . DIRECTORY_SEPARATOR . '..' 
        . DIRECTORY_SEPARATOR . 'Data' 
        . DIRECTORY_SEPARATOR . 'jc.jpeg';

    public function setUp(): void
    {
        parent::setUp();

        /** @var ModelResolverContainer $resolver */
        $resolver = app(ModelResolverContainer::class);
        $resolver->register(
            [
                'send-email' => SendTemplatedMail::class,
                'send-company-email' => SendCompanyRegistrationMail::class,
                'company-registered' => CompanyRegistered::class,
            ],
            [
                'custom-unique-action' => ['send-company-email'],
                'custom-generic-action' => ['send-email'],
                'custom-event' => ['company-registered'],
            ]
        );
    }

    private function getActionInstance() : SendCompanyRegistrationMail {
        return app(SendCompanyRegistrationMail::class);
    }

    /**
     * @dataProvider prvoiderHandleUniqueActionSuccess
     */
    public function testHandleUniqueActionSuccess($preferredLocale, $appLocale, $fallbackLocale, $success)
    {
        App::setLocale($appLocale);
        App::setFallbackLocale($fallbackLocale);
        $user = User::factory(null, ['preferred_locale' => $preferredLocale])->create();
        $company = Company::factory()->create();
        CustomActionSettings::factory()->sendMailRegistrationCompany(null, false, true, true)->create();

        $bindings = ['company' => $company, 'logo' => self::$asset];

        Mail::fake();

        if (!$success) {
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
            "Dear $user->first_name, company $company->name (last login: December 12, 2022 at 12:00 AM (UTC) December 12, 2022 at 12:00 AM (UTC))"
        );
        $this->assertTrue($mails[0]->hasAttachment(Attachment::fromPath(self::$asset)));
    }

    public static function prvoiderHandleUniqueActionSuccess()
    {
        return [
            ['en', 'fr', 'fr', true],
            ['es', 'en', 'fr', true],
            ['es', 'es', 'en', true],
            ['es', 'es', 'es', false],
        ];
    }

    public function testHandleUniqueActionWithoutSettings()
    {
        $user = User::factory()->create();
        $company = Company::factory()->create();

        $bindings = ['company' => $company, 'logo' => self::$asset];
        $this->expectExceptionMessage('action settings not set for Comhon\CustomAction\Tests\Support\SendCompanyRegistrationMail');
        $this->getActionInstance()->handle($bindings, $user);
    }

    public function testHandleUniqueActionDuplicated()
    {
        $user = User::factory()->create();
        $company = Company::factory()->create();

        // create two unique actions
        CustomActionSettings::factory()->sendMailRegistrationCompany(null, false, true, true)->create();
        CustomActionSettings::factory()->sendMailRegistrationCompany(null, false, true, true)->create();

        $bindings = ['company' => $company, 'logo' => self::$asset];
        $this->expectExceptionMessage("several 'send-company-email' actions found");
        $this->getActionInstance()->handle($bindings, $user);
    }

    public function testGetActions()
    {
        $user = User::factory()->hasConsumerAbility()->create();
        $response = $this->actingAs($user)->getJson('custom/actions');
        $response->assertJson([
            'data' => [
                "unique" => [
                    [
                        "type" => "send-company-email",
                        "name" => "send company email"
                    ]
                ],
                "generic" => [
                    [
                        "type" => "send-email",
                        "name" => "send email"
                    ]
                ]
            ]
        ]);
    }

    public function testActionShemaSuccess()
    {
        $user = User::factory()->hasConsumerAbility()->create();

        $response = $this->actingAs($user)->getJson('custom/actions/send-email/schema');
        $response->assertJson([
            'data' => [
                "binding_schema" => [
                    "to.first_name" => "string",
                    "to.name" => "string",
                    "to.last_login_at" => "datetime"
                ],
                "settings_schema" => [
                    "attachments" => "array:file"
                ],
                "localized_settings_schema" => [
                    "subject" => "template",
                    "body" => "template",
                ],
                "has_target_user" => true,
                'unique' => false,
            ]
        ]);

        $response = $this->actingAs($user)->getJson('custom/actions/send-company-email/schema');
        $response->assertJson([
            'data' => [
                "binding_schema" => [
                    "to.first_name" => "string",
                    "to.name" => "string",
                    "to.last_login_at" => "datetime",
                    "company.name" => "string",
                    "logo" => "file"
                ],
                "settings_schema" => [
                    "attachments" => "array:file"
                ],
                "localized_settings_schema" => [
                    "subject" => "template",
                    "body" => "template",
                ],
                "has_target_user" => true,
                'unique' => true,
            ]
        ]);
    }

    public function testActionShemaNotFound()
    {
        $resolver = app(ModelResolverContainer::class);
        $resolver->register([]);
        $user = User::factory()->hasConsumerAbility()->create();

        $response = $this->actingAs($user)->getJson('custom/actions/send-email/schema');
        $response->assertNotFound();
    }

    public function testGetUniqueActionNotCreated()
    {
        $user = User::factory()->hasConsumerAbility()->create();
        $response = $this->actingAs($user)->getJson('custom/action-settings/send-company-email');
        $response->assertJson([
            'data' => [
                "type" => "send-company-email",
                'settings' => [],
            ]
        ]);
    }

    public function testGetUniqueActionCreated()
    {
        $customActionSettings = CustomActionSettings::factory(null, [
            'type' => 'send-company-email',
            'settings' => [
                'subject' => 'the subject',
            ]
        ])->create();
        $user = User::factory()->hasConsumerAbility()->create();

        $response = $this->actingAs($user)->getJson('custom/action-settings/send-company-email');
        $response->assertJson([
            'data' => [
                "id" => $customActionSettings->id,
                "type" => "send-company-email",
                'settings' => [
                    'subject' => 'the subject',
                ],
            ]
        ]);

        $response = $this->actingAs($user)->getJson('custom/action-settings/' . $customActionSettings->id);
        $response->assertJson([
            'data' => [
                "id" => $customActionSettings->id,
                "type" => "send-company-email",
                'settings' => [
                    'subject' => 'the subject',
                ],
            ]
        ]);
    }

    public function testGetUniqueActionWithSeveralSettings()
    {
        CustomActionSettings::factory(null, [
            'type' => 'send-company-email',
            'settings' => []
        ])->create();
        CustomActionSettings::factory(null, [
            'type' => 'send-company-email',
            'settings' => []
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
            ]
        ])->create();
        $user = User::factory()->hasConsumerAbility()->create();
        $response = $this->actingAs($user)->getJson('custom/action-settings/' . $customActionSettings->id);
        $response->assertJson([
            'data' => [
                "type" => "send-email",
                'settings' => [],
                'id' => $customActionSettings->id,
            ]
        ]);
    }

    public function testGetActionNotFound()
    {
        $resolver = app(ModelResolverContainer::class);
        $resolver->register([]);
        $user = User::factory()->hasConsumerAbility()->create();
        $response = $this->actingAs($user)->getJson('custom/action-settings/send-email');
        $response->assertNotFound();
        $response->assertJson([
            "message" => "not found",
        ]);
    }

    public function testGetGenericActionWithKey()
    {
        $user = User::factory()->hasConsumerAbility()->create();
        $response = $this->actingAs($user)->getJson('custom/action-settings/send-email');
        $response->assertJson([
            "message" => "action must be a unique action",
        ]);
    }

    public function testUpdateGenericActionSettings()
    {
        $customActionSettings = CustomActionSettings::factory(null, [
            'type' => 'send-email',
            'settings' => [],
        ])->create();
        $newSettings = [
            'to' => [12],
        ];

        $user = User::factory()->hasConsumerAbility()->create();
        $response = $this->actingAs($user)->putJson("custom/action-settings/$customActionSettings->id", [
            'settings' => $newSettings,
        ]);
        $response->assertOk();
        $response->assertJson([
            'data' => [
                "id" => $customActionSettings->id,
                "type" => "send-email",
                "settings" => [
                    "to" => [12]
                ],
            ]
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
            'to' => [12],
        ];

        $user = User::factory()->hasConsumerAbility()->create();
        $response = $this->actingAs($user)->putJson("custom/action-settings/$customActionSettings->id", [
            'settings' => $newSettings,
        ]);
        $response->assertOk();
        $response->assertJson([
            'data' => [
                "id" => $customActionSettings->id,
                "type" => "send-company-email",
                "settings" => [
                    "to" => [12]
                ],
            ]
        ]);

        $this->assertEquals($newSettings, CustomActionSettings::findOrFail($customActionSettings->id)->settings);
    }

    /**
     *
     * @dataProvider providerActionLocalizedSettings
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
            ]
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
            ]
        ]);
        $this->assertEquals($originalSettingsFr, $localizedSettingsFr->settings);

        // get all
        $response = $this->actingAs($user)->getJson("custom/{$resource}/{$settingsContainer->id}/localized-settings");
        $response->assertJson([
            'data' => [
                ['id' => $localizedSettingsEn->id, 'locale' => 'en'],
                ['id' => $localizedSettingsFr->id, 'locale' => 'fr'],
            ]
        ]);

        // get en
        $response = $this->actingAs($user)->getJson("custom/localized-settings/{$localizedSettingsEn->id}");
        $response->assertJson([
            'data' => [
                'id' => $localizedSettingsEn->id,
                'locale' => 'en',
                'settings' => $originalSettingsEn,
            ]
        ]);
    }

    /**
     *
     * @dataProvider providerActionLocalizedSettings
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
            ]
        ]);
        $this->assertEquals($updatedSettings, ActionLocalizedSettings::where('locale', 'es')->firstOrFail()->settings);
        $this->assertEquals(1, $settingsContainer->localizedSettings()->count());
        $this->assertEquals(1, ActionLocalizedSettings::count());
    }

    /**
     *
     * @dataProvider providerActionLocalizedSettings
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

    public static function providerActionLocalizedSettings()
    {
        return [
            [CustomActionSettings::class],
            [ActionScopedSettings::class],
        ];
    }

    /**
     *
     * @return void
     */
    public function testStoreActionScopedSettings()
    {
        /** @var CustomActionSettings $customActionSettings */
        $customActionSettings = CustomActionSettings::factory(null, [
            'settings' => [],
        ])->create();
        $settingsScope1 = [
            'to' => [1],
        ];
        $scope1 = ['company' => [
            'name' => 'my company scope 1'
        ]];
        $settingsScope2 = [
            'to' => [2],
        ];
        $scope2 = ['company' => [
            'name' => 'my company scope 2'
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
            ]
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
            ]
        ]);
        $this->assertEquals($settingsScope2, $scopedSettings2->settings);

        // get all
        $response = $this->actingAs($user)->getJson("custom/action-settings/{$customActionSettings->id}/scoped-settings");
        $response->assertJson([
            'data' => [
                $scopedSettings1->id,
                $scopedSettings2->id,
            ]
        ]);

        // get scope 1
        $response = $this->actingAs($user)->getJson("custom/scoped-settings/{$scopedSettings1->id}");
        $response->assertOk();
        $response->assertJson([
            'data' => [
                'id' => $scopedSettings1->id,
                'scope' => $scope1,
                'settings' => $settingsScope1,
            ]
        ]);
    }

    public function testUpdateActionScopedSettings()
    {
        /** @var CustomActionSettings $customActionSettings */
        $customActionSettings = CustomActionSettings::factory()->create();
        $scopedSettings = new ActionScopedSettings();
        $scopedSettings->settings = [
            'to' => [1],
        ];
        $scopedSettings->scope = ['company' => [
            'name' => 'my company scope 1'
        ]];
        $scopedSettings->customActionSettings()->associate($customActionSettings);
        $scopedSettings->save();

        $updatedSettings = [
            'to' => [2],
        ];
        $updatedScope = ['company' => [
            'name' => 'my company scope 2'
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
            ]
        ]);
        $storedScopedSettings = ActionScopedSettings::findOrFail($scopedSettings->id);
        $this->assertEquals($updatedSettings, $storedScopedSettings->settings);
        $this->assertEquals($updatedScope, $storedScopedSettings->scope);
        $this->assertEquals(1, $customActionSettings->scopedSettings()->count());
        $this->assertEquals(1, ActionScopedSettings::count());
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

    public function testCallAppForbidden()
    {
        // we create a user WITHOUT the ability Ability::CONF_INI_MANAGE
        $user = User::factory()->create();
        $response = $this->actingAs($user)->getJson('custom/actions/send-email/schema');
        $response->assertForbidden();
    }

    /**
     *
     * @dataProvider providerSettingsValidation
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
                true
            ],
            [
                [
                    'subject' => 'original {{ "true }} subject',
                    'body' => 'original subject',
                ],
                false
            ],
            [
                [
                    'subject' => 'original subject',
                    'body' => 'original {{ "true }} subject',
                ],
                false
            ],
        ];
    }
}
