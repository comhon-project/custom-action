<?php

namespace Tests\Feature;

use App\Events\CompanyRegistered;
use App\Models\Company;
use App\Models\User;
use Comhon\CustomAction\Mail\Custom;
use Comhon\CustomAction\Models\ActionLocalizedSettings;
use Comhon\CustomAction\Models\CustomActionSettings;
use Comhon\CustomAction\Models\CustomEventListener;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\SendQueuedMailable;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;
use Tests\SetUpWithModelRegistration;
use Tests\Support\Utils;
use Tests\TestCase;

class EventListenerTest extends TestCase
{
    use RefreshDatabase;
    use SetUpWithModelRegistration;

    /**
     * @dataProvider providerEventListener
     *
     * @return void
     */
    public function testEventListener($addCompanyScope)
    {
        $targetUser = User::factory()->create();
        $otherUserFr = User::factory(null, ['preferred_locale' => 'fr'])->create();
        $otherUser = User::factory()->preferredTimezone('Europe/Paris')->create();
        $companyName = 'my company';
        $company = Company::factory(null, ['name' => $companyName])->create();

        // create event listener for CompanyRegistered event
        CustomEventListener::factory()->genericRegistrationCompany(
            [$otherUserFr->id, $otherUser->id],
            $addCompanyScope ? $companyName : null,
            false,
            true
        )->create();

        Bus::fake();
        Mail::fake();
        CompanyRegistered::dispatch($company, $targetUser);

        Bus::assertNothingDispatched();

        $mails = [];
        Mail::assertSent(Custom::class, 3);
        Mail::assertSent(Custom::class, function (Custom $mail) use (&$mails) {
            $mails[] = $mail;

            return true;
        });
        $firstActionAttachementPath = Utils::joinPaths(Utils::getTestPath('Data'), 'jc.jpeg');

        $mails[0]->assertHasTo($targetUser->email);
        $mails[0]->assertHasSubject("Dear $targetUser->first_name, company $company->name (last login: December 12, 2022 at 12:00 AM (UTC) December 12, 2022 at 12:00 AM (UTC))");
        $this->assertTrue($mails[0]->hasAttachment(Attachment::fromPath($firstActionAttachementPath)));

        $mails[1]->assertHasTo($otherUserFr->email);
        $mails[1]->assertHasSubject("Cher·ère $otherUserFr->first_name, la société $company->name (dernier login: 12 décembre 2022 à 00:00 (UTC) 12 décembre 2022 à 00:00 (UTC))");
        $this->assertFalse($mails[1]->hasAttachment(Attachment::fromPath($firstActionAttachementPath)));

        $mails[2]->assertHasTo($otherUser->email);
        $mails[2]->assertHasSubject("Dear $otherUser->first_name, company $company->name (last login: December 12, 2022 at 12:00 AM (UTC) December 12, 2022 at 1:00 AM (Europe/Paris))");
        $this->assertFalse($mails[1]->hasAttachment(Attachment::fromPath($firstActionAttachementPath)));
    }

    public static function providerEventListener()
    {
        return [
            [false],
            [true],
        ];
    }

    public function testEventListenerScopeNoMatch()
    {
        $targetUser = User::factory()->create();
        $otherUsers = User::factory()->count(2)->create();
        $companyName = 'my company';
        $scopeCompanyName = 'other company';
        $company = Company::factory(null, ['name' => $companyName])->create();

        // create event listener for CompanyRegistered event
        CustomEventListener::factory()->genericRegistrationCompany(
            $otherUsers->pluck('id')->all(),
            $scopeCompanyName
        )->create();

        Mail::fake();
        CompanyRegistered::dispatch($company, $targetUser);

        // scope doesn't match so listener doesn't trigger actions.
        Mail::assertNothingSent();
    }

    /**
     * @dataProvider providerEventListener
     */
    public function testEventListenerWithActionScopedSettings($useFr)
    {
        $state = $useFr ? ['preferred_locale' => 'fr'] : [];
        $company = Company::factory(null, ['name' => 'My VIP company'])->make();
        $user = User::factory(null, $state)->make();

        // create event listener for CompanyRegistered event
        CustomEventListener::factory()->genericRegistrationCompany()->create();

        Mail::fake();
        CompanyRegistered::dispatch($company, $user);

        /** @var \Illuminate\Mail\Mailable $mail */
        $mail = null;
        Mail::assertSent(Custom::class, 1);
        Mail::assertSent(Custom::class, function (Custom $customMail) use (&$mail) {
            $mail = $customMail;

            return true;
        });

        $mail->assertHasTo($user->email);

        // there is a scoped settings according company name so mail subject must contain the scoped settings.
        if ($useFr) {
            $mail->assertHasSubject("Cher·ère $user->first_name, société VIP $company->name (vérifié à: 11 novembre 2022 (UTC))");
        } else {
            $mail->assertHasSubject("Dear $user->first_name, VIP company $company->name (verified at: November 11, 2022 (UTC))");
        }
    }

    public static function providerEventListenerWithActionScopedSettings()
    {
        return [
            [false],
            [true],
        ];
    }

    public function testEventListenerQueuedActions()
    {
        $targetUser = User::factory()->create();
        $otherUsers = User::factory()->count(2)->create();
        $company = Company::factory()->create();

        // create event listener for CompanyRegistered event
        CustomEventListener::factory()->genericRegistrationCompany($otherUsers->pluck('id')->all(), null, true)->create();

        Bus::fake();
        CompanyRegistered::dispatch($company, $targetUser);

        Bus::assertDispatched(SendQueuedMailable::class, 3);
    }

    public function testGetEventListeners()
    {
        // create event listener for CompanyRegistered event
        $eventListener = CustomEventListener::factory()->genericRegistrationCompany()->create();
        $eventListener2 = CustomEventListener::factory()->genericRegistrationCompany(null, 'my company')->create();

        $user = User::factory()->hasConsumerAbility()->create();
        $response = $this->actingAs($user)->getJson('custom/events/company-registered/listeners');

        $response->assertJson([
            'data' => [
                [
                    'id' => $eventListener->id,
                    'event' => 'company-registered',
                    'scope' => null,
                ],
                [
                    'id' => $eventListener2->id,
                    'event' => 'company-registered',
                    'scope' => [
                        'company' => [
                            'name' => 'my company',
                        ],
                    ],
                ],
            ],
        ]);
    }

    public function testGetEventListenersWithNotFoundEvent()
    {
        $user = User::factory()->hasConsumerAbility()->create();
        $response = $this->actingAs($user)->getJson('custom/events/unknown-event/listeners');

        $response->assertNotFound();
    }

    public function testGetEventListenersForbidden()
    {
        $user = User::factory()->create();
        $this->actingAs($user)->getJson('custom/events/company-registered/listeners')
            ->assertForbidden();
    }

    public function testStoreEventListeners()
    {
        $scope = [
            'company' => [
                'address' => 'nowhere',
            ],
        ];
        $this->assertEquals(0, CustomEventListener::count());

        $user = User::factory()->hasConsumerAbility()->create();
        $response = $this->actingAs($user)->postJson('custom/events/company-registered/listeners', [
            'scope' => $scope,
        ]);
        $response->assertCreated();
        $this->assertEquals(1, CustomEventListener::count());
        $eventListener = CustomEventListener::all()->first();

        $response->assertJson([
            'data' => [
                'event' => 'company-registered',
                'scope' => $scope,
                'id' => $eventListener->id,
            ],
        ]);
        $this->assertEquals('company-registered', $eventListener->event);
        $this->assertEquals($scope, $eventListener->scope);
    }

    public function testStoreEventListenersWithNotFoundEvent()
    {
        $user = User::factory()->hasConsumerAbility()->create();
        $response = $this->actingAs($user)->postJson('custom/events/unkown-event/listeners', []);
        $response->assertNotFound();
    }

    public function testStoreEventListenersForbidden()
    {
        $user = User::factory()->create();
        $this->actingAs($user)->postJson('custom/events/company-registered/listeners')
            ->assertForbidden();
    }

    public function testUpdateEventListener()
    {
        $eventListener = CustomEventListener::factory()->genericRegistrationCompany()->create();
        $eventListener->actions()->attach(
            CustomActionSettings::factory()->sendMailRegistrationCompany(null, false, 'send-company-email')->create()
        );
        $this->assertEquals(null, $eventListener->scope);

        $scope = [
            'company' => [
                'address' => 'nowhere',
            ],
        ];
        $user = User::factory()->hasConsumerAbility()->create();
        $response = $this->actingAs($user)->putJson("custom/event-listeners/$eventListener->id", [
            'scope' => $scope,
        ]);
        $response->assertOk();
        $response->assertJson([
            'data' => [
                'id' => $eventListener->id,
                'event' => 'company-registered',
                'scope' => $scope,
            ],
        ]);
        $storedEventListener = CustomEventListener::findOrFail($eventListener->id);
        $this->assertEquals($scope, $storedEventListener->scope);
    }

    public function testUpdateEventListenerForbidden()
    {
        $eventListener = CustomEventListener::factory()->genericRegistrationCompany()->create();

        $user = User::factory()->create();
        $this->actingAs($user)->putJson("custom/event-listeners/$eventListener->id")
            ->assertForbidden();
    }

    public function testDeleteEventListeners()
    {
        $eventListener = CustomEventListener::factory()->genericRegistrationCompany()->create();
        $eventListener->actions()->attach(
            CustomActionSettings::factory()->sendMailRegistrationCompany(null, false, 'send-company-email')->create()
        );
        $this->assertEquals(1, CustomEventListener::count());
        $this->assertEquals(2, CustomActionSettings::count());
        $this->assertEquals(6, ActionLocalizedSettings::count());

        $user = User::factory()->hasConsumerAbility()->create();
        $response = $this->actingAs($user)->delete("custom/event-listeners/$eventListener->id");
        $response->assertNoContent();
        $this->assertEquals(0, CustomEventListener::count());

        // unlike generic actions, unique actions MUST NOT be deleted (only detached from event listener)
        // that why there should only be one action left
        $this->assertEquals(1, CustomActionSettings::count());
        $this->assertEquals(2, ActionLocalizedSettings::count());
    }

    public function testDeleteEventListenersForbidden()
    {
        $eventListener = CustomEventListener::factory()->genericRegistrationCompany()->create();
        $eventListener->actions()->attach(
            CustomActionSettings::factory()->sendMailRegistrationCompany(null, false, 'send-company-email')->create()
        );

        $user = User::factory()->create();
        $this->actingAs($user)->delete("custom/event-listeners/$eventListener->id")
            ->assertForbidden();

        $this->assertEquals(1, CustomEventListener::count());
        $this->assertEquals(2, CustomActionSettings::count());
        $this->assertEquals(6, ActionLocalizedSettings::count());
    }

    public function testGetEventListenerActions()
    {
        $toUser = User::factory()->create();

        // create event listener for CompanyRegistered event
        $eventListener = CustomEventListener::factory()->genericRegistrationCompany([$toUser->id])->create();

        $user = User::factory()->hasConsumerAbility()->create();
        $response = $this->actingAs($user)->getJson(
            "custom/event-listeners/$eventListener->id/actions"
        );

        $customActionSettingss = CustomActionSettings::all(['id']);
        $response->assertJson([
            'data' => [
                [
                    'id' => $customActionSettingss[0]->id,
                    'type' => 'send-email',
                ],
                [
                    'id' => $customActionSettingss[1]->id,
                    'type' => 'send-email',
                ],
            ],
        ]);
    }

    public function testGetEventListenerActionsForbidden()
    {
        $toUser = User::factory()->create();

        // create event listener for CompanyRegistered event
        $eventListener = CustomEventListener::factory()->genericRegistrationCompany([$toUser->id])->create();

        $user = User::factory()->create();
        $this->actingAs($user)->getJson("custom/event-listeners/$eventListener->id/actions")
            ->assertForbidden();
    }

    public function testStoreEventListenerAction()
    {
        // create event listener for CompanyRegistered event
        $eventListener = CustomEventListener::factory()->create();
        $this->assertEquals(0, $eventListener->actions()->count());

        $user = User::factory()->hasConsumerAbility()->create();
        $actionValues = [
            'type' => 'send-email',
            'settings' => [
                'to' => [12],
                'attachments' => ['logo'],
            ],
        ];
        $response = $this->actingAs($user)->postJson("custom/event-listeners/$eventListener->id/actions", $actionValues);
        $response->assertOk();
        $this->assertEquals(1, CustomActionSettings::count());
        $this->assertEquals(1, $eventListener->actions()->count());
        $customActionSettings = CustomActionSettings::all()->first();
        $actionValues['id'] = $customActionSettings->id;

        $response->assertJson([
            'data' => $actionValues,
        ]);
        $this->assertEquals('send-email', $customActionSettings->type);
        $this->assertEquals($actionValues['settings'], $customActionSettings->settings);
    }

    public function testStoreEventListenerActionForbidden()
    {
        // create event listener for CompanyRegistered event
        $eventListener = CustomEventListener::factory()->create();
        $this->assertEquals(0, $eventListener->actions()->count());

        $user = User::factory()->create();
        $this->actingAs($user)->postJson("custom/event-listeners/$eventListener->id/actions")
            ->assertForbidden();
    }

    public function testSyncEventListenerAction()
    {
        // create event listener for CompanyRegistered event
        $eventListener = CustomEventListener::factory()->create();
        $customActionSettings = CustomActionSettings::factory()
            ->sendMailRegistrationCompany(null, false, 'send-company-email')
            ->create();

        $this->assertEquals(0, $eventListener->actions()->count());

        $user = User::factory()->hasConsumerAbility()->create();
        $actionValues = [
            'type' => 'send-company-email',
        ];
        $response = $this->actingAs($user)->postJson("custom/event-listeners/$eventListener->id/actions/sync", $actionValues);
        $response->assertOk();
        $this->assertEquals(1, CustomActionSettings::count());
        $this->assertEquals(1, $eventListener->actions()->count());

        $response->assertJson([
            'data' => ['id' => $customActionSettings->id],
        ]);
        $this->assertEquals('send-company-email', $customActionSettings->type);
    }

    public function testSyncEventListenerActionForbidden()
    {
        // create event listener for CompanyRegistered event
        $eventListener = CustomEventListener::factory()->create();
        CustomActionSettings::factory()
            ->sendMailRegistrationCompany(null, false, 'send-company-email')
            ->create();

        $user = User::factory()->create();
        $this->actingAs($user)->postJson("custom/event-listeners/$eventListener->id/actions/sync", [
            'type' => 'send-company-email',
        ])->assertForbidden();
    }

    public function testRemoveEventListenerAction()
    {
        // create event listener for CompanyRegistered event
        $eventListener = CustomEventListener::factory()->genericRegistrationCompany()->create();
        $eventListener->actions()->attach(
            CustomActionSettings::factory()->sendMailRegistrationCompany(null, false, 'send-company-email')->create()
        );
        $this->assertCount(2, $eventListener->actions);
        $actionMustBeDeleted = $eventListener->actions[0];
        $actionMustNotBeDeleted = $eventListener->actions[1];

        $user = User::factory()->hasConsumerAbility()->create();
        $response = $this->actingAs($user)->postJson(
            "custom/event-listeners/$eventListener->id/actions/$actionMustBeDeleted->id/remove"
        );
        $response->assertNoContent();
        $this->assertEquals(1, $eventListener->actions()->count());
        $this->assertEquals(1, CustomActionSettings::count());
        $this->assertNull(CustomActionSettings::find($actionMustBeDeleted->id));

        $response = $this->actingAs($user)->postJson(
            "custom/event-listeners/$eventListener->id/actions/$actionMustNotBeDeleted->id/remove"
        );
        $response->assertNoContent();
        $this->assertEquals(0, $eventListener->actions()->count());

        // unique action must be preserved (even if it is detached)
        $this->assertEquals(1, CustomActionSettings::count());
        $this->assertNotNull(CustomActionSettings::find($actionMustNotBeDeleted->id));
    }

    public function testRemoveEventListenerActionForbidden()
    {
        // create event listener for CompanyRegistered event
        $eventListener = CustomEventListener::factory()->genericRegistrationCompany()->create();
        $eventListener->actions()->attach(
            CustomActionSettings::factory()->sendMailRegistrationCompany(null, false, 'send-company-email')->create()
        );
        $action = $eventListener->actions[0];

        $user = User::factory()->create();
        $this->actingAs($user)->postJson(
            "custom/event-listeners/$eventListener->id/actions/$action->id/remove"
        )->assertForbidden();

        $this->assertCount(2, $eventListener->actions);
    }
}
