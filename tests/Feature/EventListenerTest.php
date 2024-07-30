<?php

namespace Tests\Feature;

use App\Events\CompanyRegistered;
use App\Models\Company;
use App\Models\User;
use Comhon\CustomAction\Mail\Custom;
use Comhon\CustomAction\Models\ActionLocalizedSettings;
use Comhon\CustomAction\Models\ActionSettings;
use Comhon\CustomAction\Models\EventAction;
use Comhon\CustomAction\Models\EventListener;
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
        EventListener::factory()->genericRegistrationCompany(
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

        $listener = EventListener::factory(['event' => 'company-registered'])
            ->create();

        $receiver = User::factory(['preferred_locale' => 'fr'])->create();
        $actionSettings = ActionSettings::factory([
            'settings' => [
                'to_emails' => ['john.doe@gmail.com'],
                'to_receivers' => [['receiver_type' => 'user', 'receiver_id' => $receiver->id]],
                'to_bindings_emails' => ['responsibles.*.email'],
                'to_bindings_receivers' => ['user'],
            ],
        ])->create();
        EventAction::factory()
            ->for($listener, 'eventListener')
            ->for($actionSettings, 'actionSettings')
            ->create();

        ActionLocalizedSettings::factory()->for($actionSettings, 'localizable')->emailSettings('en')->create();
        ActionLocalizedSettings::factory()->for($actionSettings, 'localizable')->emailSettings('fr')->create();

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
        EventListener::factory()->genericRegistrationCompany(
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
        EventListener::factory()->genericRegistrationCompany()->create();

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
        EventListener::factory()->genericRegistrationCompany($otherUsers->pluck('id')->all(), null, true)->create();

        Bus::fake();
        CompanyRegistered::dispatch($company, $targetUser);

        Bus::assertDispatched(SendQueuedMailable::class, 3);
    }

    public function testGetEventListeners()
    {
        // create event listener for CompanyRegistered event
        $eventListener = EventListener::factory()->genericRegistrationCompany()->create();
        $eventListener2 = EventListener::factory()->genericRegistrationCompany(null, 'my company')->create();

        $user = User::factory()->hasConsumerAbility()->create();
        $response = $this->actingAs($user)->getJson('custom/events/company-registered/listeners');

        $response->assertJson([
            'data' => [
                [
                    'id' => $eventListener->id,
                    'event' => 'company-registered',
                    'name' => 'My Custom Event Listener',
                    'scope' => null,
                ],
                [
                    'id' => $eventListener2->id,
                    'event' => 'company-registered',
                    'name' => 'My Custom Event Listener',
                    'scope' => [
                        'company' => [
                            'name' => 'my company',
                        ],
                    ],
                ],
            ],
        ]);
    }

    public function testGetEventListenersWithFilter()
    {
        // create event listener for CompanyRegistered event
        $eventListener = EventListener::factory(['name' => 'my one'])->genericRegistrationCompany()->create();
        EventListener::factory(['name' => 'my two'])->genericRegistrationCompany()->create();

        $user = User::factory()->hasConsumerAbility()->create();
        $params = http_build_query(['name' => 'one']);
        $this->actingAs($user)->getJson("custom/events/company-registered/listeners?$params")
            ->assertJsonCount(1, 'data')
            ->assertJson([
                'data' => [
                    [
                        'id' => $eventListener->id,
                        'event' => 'company-registered',
                        'name' => 'my one',
                        'scope' => null,
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
        $this->assertEquals(0, EventListener::count());

        $user = User::factory()->hasConsumerAbility()->create();
        $response = $this->actingAs($user)->postJson('custom/events/company-registered/listeners', [
            'scope' => $scope,
            'name' => 'my event listener',
        ]);
        $response->assertCreated();
        $this->assertEquals(1, EventListener::count());
        $eventListener = EventListener::all()->first();

        $response->assertJson([
            'data' => [
                'event' => 'company-registered',
                'scope' => $scope,
                'name' => 'my event listener',
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
        $eventListener = EventListener::factory()->genericRegistrationCompany()->create();
        $this->assertNull($eventListener->scope);

        $scope = [
            'company' => [
                'address' => 'nowhere',
            ],
        ];
        $user = User::factory()->hasConsumerAbility()->create();
        $response = $this->actingAs($user)->putJson("custom/event-listeners/$eventListener->id", [
            'scope' => $scope,
            'name' => 'updated event listener',
        ]);
        $response->assertOk();
        $response->assertJson([
            'data' => [
                'id' => $eventListener->id,
                'event' => 'company-registered',
                'scope' => $scope,
                'name' => 'updated event listener',
            ],
        ]);
        $storedEventListener = EventListener::findOrFail($eventListener->id);
        $this->assertEquals($scope, $storedEventListener->scope);
    }

    public function testUpdateEventListenerForbidden()
    {
        $eventListener = EventListener::factory()->genericRegistrationCompany()->create();

        $user = User::factory()->create();
        $this->actingAs($user)->putJson("custom/event-listeners/$eventListener->id")
            ->assertForbidden();
    }

    public function testDeleteEventListeners()
    {
        $eventListener = EventListener::factory()->genericRegistrationCompany()->create();

        $this->assertEquals(1, EventListener::count());
        $this->assertEquals(1, ActionSettings::count());
        $this->assertEquals(4, ActionLocalizedSettings::count());

        $user = User::factory()->hasConsumerAbility()->create();
        $response = $this->actingAs($user)->delete("custom/event-listeners/$eventListener->id");
        $response->assertNoContent();
        $this->assertEquals(0, EventListener::count());

        $this->assertEquals(0, ActionSettings::count());
        $this->assertEquals(0, ActionLocalizedSettings::count());
    }

    public function testDeleteEventListenersForbidden()
    {
        $eventListener = EventListener::factory()->genericRegistrationCompany()->create();

        $user = User::factory()->create();
        $this->actingAs($user)->delete("custom/event-listeners/$eventListener->id")
            ->assertForbidden();

        $this->assertEquals(1, EventListener::count());
        $this->assertEquals(1, ActionSettings::count());
        $this->assertEquals(4, ActionLocalizedSettings::count());
    }

    public function testGetEventListenerActions()
    {
        $toUser = User::factory()->create();

        // create event listener for CompanyRegistered event
        $eventListener = EventListener::factory()->genericRegistrationCompany([$toUser->id])->create();

        $user = User::factory()->hasConsumerAbility()->create();
        $response = $this->actingAs($user)->getJson(
            "custom/event-listeners/$eventListener->id/actions"
        );

        $actionSettingss = ActionSettings::all(['id']);
        $response->assertJson([
            'data' => [
                [
                    'id' => $actionSettingss[0]->id,
                    'type' => 'send-email',
                ],
                [
                    'id' => $actionSettingss[1]->id,
                    'type' => 'send-email',
                ],
            ],
        ]);
    }

    public function testGetEventListenerActionsWithFilter()
    {
        $toUser = User::factory()->create();

        // create event listener for CompanyRegistered event
        $eventListener = EventListener::factory([
            'event' => 'company-registered',
        ])->create();
        $eventAction = EventAction::factory(['name' => 'my one'])
            ->sendMailRegistrationCompany()
            ->for($eventListener, 'eventListener')->create();
        EventAction::factory(['name' => 'my two'])
            ->sendMailRegistrationCompany()
            ->for($eventListener, 'eventListener')->create();

        $user = User::factory()->hasConsumerAbility()->create();
        $params = http_build_query(['name' => 'one']);
        $this->actingAs($user)->getJson("custom/event-listeners/$eventListener->id/actions?$params")
            ->assertJsonCount(1, 'data')->assertJson([
                'data' => [
                    [
                        'id' => $eventAction->id,
                        'type' => 'send-email',
                        'name' => 'my one',
                    ],
                ],
            ]);
    }

    public function testGetEventListenerActionsForbidden()
    {
        $toUser = User::factory()->create();

        // create event listener for CompanyRegistered event
        $eventListener = EventListener::factory()->genericRegistrationCompany([$toUser->id])->create();

        $user = User::factory()->create();
        $this->actingAs($user)->getJson("custom/event-listeners/$eventListener->id/actions")
            ->assertForbidden();
    }

    public function testStoreEventListenerActionSuccess()
    {
        // create event listener for CompanyRegistered event
        $eventListener = EventListener::factory()->create();
        $this->assertEquals(0, $eventListener->eventActions()->count());

        $user = User::factory()->hasConsumerAbility()->create();
        $actionValues = [
            'name' => 'my custom event listener',
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
        $this->assertEquals(1, ActionSettings::count());
        $this->assertEquals(1, $eventListener->eventActions()->count());
        $actionSettings = ActionSettings::all()->first();
        $actionValues['id'] = $actionSettings->id;

        $response->assertJson([
            'data' => [
                'name' => $actionValues['name'],
                'type' => $actionValues['type'],
                'action_settings' => [
                    'id' => $actionSettings->id,
                    'settings' => $actionValues['settings'],
                ],
            ],
        ]);
        $this->assertEquals('send-email', $actionSettings->eventAction->type);
        $this->assertEquals($actionValues['settings'], $actionSettings->settings);
    }

    public function testStoreEventListenerBadActionSuccess()
    {
        // create event listener for CompanyRegistered event
        $eventListener = EventListener::factory([
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
                'message' => 'Action bad-action not found. (and 2 more errors)',
                'errors' => [
                    'type' => [
                        'Action bad-action not found.',
                        'The action bad-action is not an action triggerable from event.',
                    ],
                    'name' => [
                        'The name field is required.',
                    ],
                ],
            ]);
    }

    public function testStoreEventListenerActionForbidden()
    {
        // create event listener for CompanyRegistered event
        $eventListener = EventListener::factory()->create();
        $this->assertEquals(0, $eventListener->eventActions()->count());

        $user = User::factory()->create();
        $this->actingAs($user)->postJson("custom/event-listeners/$eventListener->id/actions")
            ->assertForbidden();
    }

    public function testGetEventListenerAction()
    {
        // create event listener for CompanyRegistered event
        $eventListener = EventListener::factory()->genericRegistrationCompany()->create();
        $action = $eventListener->eventActions[0];

        $user = User::factory()->hasConsumerAbility()->create();
        $this->actingAs($user)->getJson("custom/event-actions/$action->id")
            ->assertOk()
            ->assertJson([
                'data' => [
                    'id' => $action->id,
                    'name' => $action->name,
                    'event_listener_id' => $action->event_listener_id,
                    'action_settings_id' => $action->action_settings_id,
                ],
            ]);
    }

    public function testGetEventListenerActionForbidden()
    {
        // create event listener for CompanyRegistered event
        $eventListener = EventListener::factory()->genericRegistrationCompany()->create();
        $action = $eventListener->eventActions[0];

        $user = User::factory()->create();
        $this->actingAs($user)->getJson("custom/event-actions/$action->id")
            ->assertForbidden();
    }

    public function testUpdateEventListenerAction()
    {
        // create event listener for CompanyRegistered event
        $eventListener = EventListener::factory()->genericRegistrationCompany()->create();

        $this->assertCount(1, $eventListener->eventActions);
        $action = $eventListener->eventActions[0];
        $updateName = 'updated event action';

        $user = User::factory()->hasConsumerAbility()->create();
        $this->actingAs($user)->putJson("custom/event-actions/$action->id", [
            'name' => $updateName,
        ])->assertOk()
            ->assertJson([
                'data' => [
                    'name' => $updateName,
                ],
            ]);
        $this->assertEquals($updateName, $action->refresh()->name);
    }

    public function testUpdateEventListenerActionForbidden()
    {
        // create event listener for CompanyRegistered event
        $eventListener = EventListener::factory()->genericRegistrationCompany()->create();
        $action = $eventListener->eventActions[0];

        $user = User::factory()->create();
        $this->actingAs($user)->putJson("custom/event-actions/$action->id")->assertForbidden();
    }

    public function testDeleteEventAction()
    {
        // create event listener for CompanyRegistered event
        $eventListener = EventListener::factory()->genericRegistrationCompany()->create();

        $this->assertCount(1, $eventListener->eventActions);
        $action = $eventListener->eventActions[0];

        $user = User::factory()->hasConsumerAbility()->create();
        $response = $this->actingAs($user)->deleteJson(
            "custom/event-actions/$action->id"
        );
        $response->assertNoContent();
        $this->assertEquals(0, $eventListener->eventActions()->count());
        $this->assertEquals(0, ActionSettings::count());
        $this->assertNull(ActionSettings::find($action->id));
    }

    public function testDeleteEventActionForbidden()
    {
        // create event listener for CompanyRegistered event
        $eventListener = EventListener::factory()->genericRegistrationCompany()->create();

        $action = $eventListener->eventActions[0];

        $user = User::factory()->create();
        $this->actingAs($user)->deleteJson(
            "custom/event-actions/$action->id"
        )->assertForbidden();

        $this->assertEquals(1, $eventListener->eventActions()->count());
    }
}
