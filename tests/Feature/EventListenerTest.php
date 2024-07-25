<?php

namespace Tests\Feature;

use App\Events\CompanyRegistered;
use App\Models\Company;
use App\Models\User;
use Comhon\CustomAction\Mail\Custom;
use Comhon\CustomAction\Models\ActionLocalizedSettings;
use Comhon\CustomAction\Models\CustomActionSettings;
use Comhon\CustomAction\Models\CustomEventAction;
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
        $otherUserFr = User::factory(['preferred_locale' => 'fr'])->create();
        $otherUser = User::factory()->preferredTimezone('Europe/Paris')->create();
        $companyName = 'my company';
        $company = Company::factory(['name' => $companyName])->create();

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
        $mails[0]->assertHasSubject("Dear $targetUser->first_name, company $company->name (login: December 12, 2022 at 12:00 AM (UTC) December 12, 2022 at 12:00 AM (UTC))");
        $this->assertTrue($mails[0]->hasAttachment(Attachment::fromPath($firstActionAttachementPath)));

        $mails[1]->assertHasTo($otherUserFr->email);
        $mails[1]->assertHasSubject("Cher·ère $otherUserFr->first_name, la société $company->name (login: 12 décembre 2022 à 00:00 (UTC) 12 décembre 2022 à 00:00 (UTC))");
        $this->assertFalse($mails[1]->hasAttachment(Attachment::fromPath($firstActionAttachementPath)));

        $mails[2]->assertHasTo($otherUser->email);
        $mails[2]->assertHasSubject("Dear $otherUser->first_name, company $company->name (login: December 12, 2022 at 12:00 AM (UTC) December 12, 2022 at 1:00 AM (Europe/Paris))");
        $this->assertFalse($mails[1]->hasAttachment(Attachment::fromPath($firstActionAttachementPath)));
    }

    public static function providerEventListener()
    {
        return [
            [false],
            [true],
        ];
    }

    public function testEventListenerWithAllAvailableToProperties()
    {
        $user = User::factory()->create();
        $companyName = 'my company';
        $company = Company::factory(['name' => $companyName])->create();

        $listener = CustomEventListener::factory(['event' => 'company-registered'])
            ->create();

        $receiver = User::factory(['preferred_locale' => 'fr'])->create();
        $customActionSettings = CustomActionSettings::factory([
            'settings' => [
                'to_emails' => ['john.doe@gmail.com'],
                'to_receivers' => [['receiver_type' => 'user', 'receiver_id' => $receiver->id]],
                'to_bindings_emails' => ['responsibles.*.email'],
                'to_bindings_receivers' => ['user'],
            ],
        ])->create();
        CustomEventAction::factory()
            ->for($listener, 'eventListener')
            ->for($customActionSettings, 'actionSettings')
            ->create();

        ActionLocalizedSettings::factory()->for($customActionSettings, 'localizable')->emailSettings('en')->create();
        ActionLocalizedSettings::factory()->for($customActionSettings, 'localizable')->emailSettings('fr')->create();

        Bus::fake();
        Mail::fake();
        CompanyRegistered::dispatch($company, $user);

        Bus::assertNothingDispatched();

        $mails = [];
        Mail::assertSent(Custom::class, 5);
        Mail::assertSent(Custom::class, function (Custom $mail) use (&$mails) {
            $mails[] = $mail;

            return true;
        });

        $mails[0]->assertHasTo($receiver->email);
        $mails[0]->assertHasSubject("Cher·ère $receiver->first_name, la société $company->name");

        $mails[1]->assertHasTo('john.doe@gmail.com');
        $mails[1]->assertHasSubject("Dear , company $company->name");

        $mails[2]->assertHasTo($user->email);
        $mails[2]->assertHasSubject("Dear $user->first_name, company $company->name");

        $mails[3]->assertHasTo('responsible_one@gmail.com');
        $mails[3]->assertHasSubject("Dear , company $company->name");

        $mails[4]->assertHasTo('responsible_two@gmail.com');
        $mails[4]->assertHasSubject("Dear , company $company->name");
    }

    public function testEventListenerScopeNoMatch()
    {
        $targetUser = User::factory()->create();
        $otherUsers = User::factory()->count(2)->create();
        $companyName = 'my company';
        $scopeCompanyName = 'other company';
        $company = Company::factory(['name' => $companyName])->create();

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
        $company = Company::factory(['name' => 'My VIP company'])->create();
        $user = User::factory($state)->create();

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
        $this->assertNull($eventListener->scope);

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

        $this->assertEquals(1, CustomEventListener::count());
        $this->assertEquals(1, CustomActionSettings::count());
        $this->assertEquals(4, ActionLocalizedSettings::count());

        $user = User::factory()->hasConsumerAbility()->create();
        $response = $this->actingAs($user)->delete("custom/event-listeners/$eventListener->id");
        $response->assertNoContent();
        $this->assertEquals(0, CustomEventListener::count());

        $this->assertEquals(0, CustomActionSettings::count());
        $this->assertEquals(0, ActionLocalizedSettings::count());
    }

    public function testDeleteEventListenersForbidden()
    {
        $eventListener = CustomEventListener::factory()->genericRegistrationCompany()->create();

        $user = User::factory()->create();
        $this->actingAs($user)->delete("custom/event-listeners/$eventListener->id")
            ->assertForbidden();

        $this->assertEquals(1, CustomEventListener::count());
        $this->assertEquals(1, CustomActionSettings::count());
        $this->assertEquals(4, ActionLocalizedSettings::count());
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

    public function testStoreEventListenerActionSuccess()
    {
        // create event listener for CompanyRegistered event
        $eventListener = CustomEventListener::factory()->create();
        $this->assertEquals(0, $eventListener->eventActions()->count());

        $user = User::factory()->hasConsumerAbility()->create();
        $actionValues = [
            'type' => 'send-email',
            'settings' => [
                'to_receivers' => [
                    ['receiver_type' => 'user', 'receiver_id' => User::factory()->create()->id],
                ],
                'to_bindings_receivers' => ['user'],
                'attachments' => ['logo'],
            ],
        ];
        $response = $this->actingAs($user)->postJson("custom/event-listeners/$eventListener->id/actions", $actionValues);
        $response->assertCreated();
        $this->assertEquals(1, CustomActionSettings::count());
        $this->assertEquals(1, $eventListener->eventActions()->count());
        $customActionSettings = CustomActionSettings::all()->first();
        $actionValues['id'] = $customActionSettings->id;

        $response->assertJson([
            'data' => [
                'type' => $actionValues['type'],
                'action_settings' => [
                    'id' => $customActionSettings->id,
                    'settings' => $actionValues['settings'],
                ],
            ],
        ]);
        $this->assertEquals('send-email', $customActionSettings->eventAction->type);
        $this->assertEquals($actionValues['settings'], $customActionSettings->settings);
    }

    public function testStoreEventListenerBadActionSuccess()
    {
        // create event listener for CompanyRegistered event
        $eventListener = CustomEventListener::factory([
            'event' => 'bad-event',
        ])->create();
        $this->assertEquals(0, $eventListener->eventActions()->count());

        $user = User::factory()->hasConsumerAbility()->create();
        $actionValues = [
            'type' => 'bad-action',
            'settings' => [],
        ];
        $this->actingAs($user)->postJson("custom/event-listeners/$eventListener->id/actions", $actionValues)
            ->assertUnprocessable()
            ->assertJson([
                'message' => 'Action bad-action not found. (and 1 more error)',
                'errors' => [
                    'type' => [
                        'Action bad-action not found.',
                        'The action bad-action is not an action triggerable from event.',
                    ],
                ],
            ]);
    }

    public function testStoreEventListenerActionForbidden()
    {
        // create event listener for CompanyRegistered event
        $eventListener = CustomEventListener::factory()->create();
        $this->assertEquals(0, $eventListener->eventActions()->count());

        $user = User::factory()->create();
        $this->actingAs($user)->postJson("custom/event-listeners/$eventListener->id/actions")
            ->assertForbidden();
    }

    public function testRemoveEventListenerAction()
    {
        // create event listener for CompanyRegistered event
        $eventListener = CustomEventListener::factory()->genericRegistrationCompany()->create();

        $this->assertCount(1, $eventListener->eventActions);
        $action = $eventListener->eventActions[0];

        $user = User::factory()->hasConsumerAbility()->create();
        $response = $this->actingAs($user)->deleteJson(
            "custom/event-actions/$action->id"
        );
        $response->assertNoContent();
        $this->assertEquals(0, $eventListener->eventActions()->count());
        $this->assertEquals(0, CustomActionSettings::count());
        $this->assertNull(CustomActionSettings::find($action->id));
    }

    public function testRemoveEventListenerActionForbidden()
    {
        // create event listener for CompanyRegistered event
        $eventListener = CustomEventListener::factory()->genericRegistrationCompany()->create();

        $action = $eventListener->eventActions[0];

        $user = User::factory()->create();
        $this->actingAs($user)->deleteJson(
            "custom/event-actions/$action->id"
        )->assertForbidden();

        $this->assertEquals(1, $eventListener->eventActions()->count());
    }
}
