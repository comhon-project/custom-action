<?php

namespace Tests\Feature;

use App\Events\CompanyRegistered;
use App\Events\MyEventWithoutBindings;
use App\Models\Company;
use App\Models\User;
use Comhon\CustomAction\Actions\QueueAutomaticEmail;
use Comhon\CustomAction\Events\EventActionError;
use Comhon\CustomAction\Mail\Custom;
use Comhon\CustomAction\Models\DefaultSetting;
use Comhon\CustomAction\Models\EventListener;
use Comhon\CustomAction\Models\LocalizedSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\SetUpWithModelRegistrationTrait;
use Tests\Support\Caller;
use Tests\Support\Utils;
use Tests\TestCase;

class EventDispatchTest extends TestCase
{
    use RefreshDatabase;
    use SetUpWithModelRegistrationTrait;

    #[DataProvider('providerEventListener')]
    public function test_event_listener_success($addCompanyScope)
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

        Queue::fake();
        Mail::fake();

        CompanyRegistered::dispatch($company, $targetUser);

        Queue::assertNothingPushed();

        $mails = [];
        Mail::assertSent(Custom::class, 3);
        Mail::assertSent(Custom::class, function (Custom $mail) use (&$mails) {
            $mails[] = $mail;

            return true;
        });
        $firstActionAttachementPath = Utils::joinPaths(Utils::getTestPath('Data'), 'jc.jpeg');

        $mails[0]->assertHasTo($targetUser->email);
        $mails[0]->assertHasSubject("Dear $targetUser->first_name, company $company->name en (login: December 12, 2022 at 12:00 AM (UTC) December 12, 2022 at 12:00 AM (UTC))");
        $this->assertTrue($mails[0]->hasAttachment(Attachment::fromPath($firstActionAttachementPath)));

        $mails[1]->assertHasTo($otherUserFr->email);
        $mails[1]->assertHasSubject("Cher·ère $otherUserFr->first_name, la société $company->name fr (login: 12 décembre 2022 à 00:00 (UTC) 12 décembre 2022 à 00:00 (UTC))");
        $this->assertFalse($mails[1]->hasAttachment(Attachment::fromPath($firstActionAttachementPath)));

        $mails[2]->assertHasTo($otherUser->email);
        $mails[2]->assertHasSubject("Dear $otherUser->first_name, company $company->name en (login: December 12, 2022 at 12:00 AM (UTC) December 12, 2022 at 1:00 AM (Europe/Paris))");
        $this->assertFalse($mails[1]->hasAttachment(Attachment::fromPath($firstActionAttachementPath)));
    }

    public static function providerEventListener()
    {
        return [
            [false],
            [true],
        ];
    }

    public function test_event_listener_with_event_without_bindings()
    {
        // create event listener for CompanyRegistered event
        $setting = DefaultSetting::factory()
            ->withEventAction('my-action-without-bindings')
            ->create();

        $eventListener = $setting->action->eventListener;
        $eventListener->event = 'my-event-without-bindings';
        $eventListener->save();

        $this->partialMock(Caller::class, function (MockInterface $mock) use ($setting) {
            $mock->shouldReceive('call')->once()->withArgs(function ($defaultSetting, $bindingsContainer) use ($setting) {
                return $setting->is($defaultSetting) && $bindingsContainer === null;
            });
        });

        MyEventWithoutBindings::dispatch();
    }

    public function test_event_listener_with_all_available_to_properties()
    {
        /** @var User $user */
        $user = User::factory()->create();
        $companyName = 'my company';
        $company = Company::factory(['name' => $companyName])->create();

        $receiver = User::factory(['preferred_locale' => 'fr'])->create();
        $defaultSetting = DefaultSetting::factory([
            'settings' => [
                'recipients' => ['to' => [
                    'static' => [
                        'mailables' => [
                            ['recipient_type' => 'user', 'recipient_id' => $receiver->id],
                        ],
                        'emails' => ['john.doe@gmail.com'],
                    ],
                    'bindings' => [
                        'mailables' => ['user'],
                        'emails' => ['responsibles.*.email'],
                    ],
                ]],
            ],
        ])->withEventAction(null, 'company-registered')->create();

        LocalizedSetting::factory()->for($defaultSetting, 'localizable')->emailSettings('en')->create();
        LocalizedSetting::factory()->for($defaultSetting, 'localizable')->emailSettings('fr')->create();

        Queue::fake();
        Mail::fake();

        CompanyRegistered::dispatch($company, $user);

        Queue::assertNothingPushed();

        $mails = [];
        Mail::assertSent(Custom::class, 5);
        Mail::assertSent(Custom::class, function (Custom $mail) use (&$mails) {
            $mails[] = $mail;

            return true;
        });

        $mails[0]->assertHasTo($receiver->email);
        $mails[0]->assertHasSubject("Cher·ère $receiver->first_name, la société $company->name fr");

        $mails[1]->assertHasTo('john.doe@gmail.com');
        $mails[1]->assertHasSubject("Dear , company $company->name en");

        $mails[2]->assertHasTo($user->email);
        $mails[2]->assertHasSubject("Dear $user->first_name, company $company->name en");

        $mails[3]->assertHasTo('responsible_one@gmail.com');
        $mails[3]->assertHasSubject("Dear , company $company->name en");

        $mails[4]->assertHasTo('responsible_two@gmail.com');
        $mails[4]->assertHasSubject("Dear , company $company->name en");
    }

    public function test_event_listener_scope_no_match()
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

    #[DataProvider('providerEventListener')]
    public function test_event_listener_with_action_scoped_settings($useFr)
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
            $mail->assertHasSubject("Cher·ère $user->first_name, société VIP $company->name fr (vérifié à: 11 novembre 2022 (UTC))");
        } else {
            $mail->assertHasSubject("Dear $user->first_name, VIP company $company->name en (verified at: November 11, 2022 (UTC))");
        }
    }

    public function test_event_listener_queued_actions()
    {
        $targetUser = User::factory()->create();
        $otherUsers = User::factory()->count(2)->create();
        $company = Company::factory()->create();

        // create event listener for CompanyRegistered event
        EventListener::factory()->genericRegistrationCompany($otherUsers->pluck('id')->all(), null, true)->create();

        Queue::fake();
        CompanyRegistered::dispatch($company, $targetUser);

        Queue::assertPushed(QueueAutomaticEmail::class, 2);
    }

    public function test_event_listener_with_cc_bcc_success()
    {
        $targetUser = User::factory()->create();
        $otherUser = User::factory()->create();
        $company = Company::factory()->create();

        // create event listener for CompanyRegistered event
        EventListener::factory()->genericRegistrationCompany()->create();
        $defaultSetting = DefaultSetting::firstOrFail();
        $settings = $defaultSetting->settings;
        $settings['recipients']['cc'] = [
            'static' => [
                'mailables' => [['recipient_type' => 'user', 'recipient_id' => $otherUser->id]],
                'emails' => ['foo@cc.com'],
            ],
            'bindings' => [
                'mailables' => ['user'],
                'emails' => ['responsibles.*.email'],
            ],
        ];
        $settings['recipients']['bcc'] = [
            'static' => [
                'mailables' => [['recipient_type' => 'user', 'recipient_id' => $otherUser->id]],
                'emails' => ['foo@bcc.com'],
            ],
            'bindings' => [
                'mailables' => ['user'],
                'emails' => ['responsibles.*.email'],
            ],
        ];
        $defaultSetting->settings = $settings;
        $defaultSetting->save();

        Queue::fake();
        Mail::fake();

        CompanyRegistered::dispatch($company, $targetUser);

        Queue::assertNothingPushed();

        $mails = [];
        Mail::assertSent(Custom::class, 1);
        Mail::assertSent(Custom::class, function (Custom $mail) use (&$mails) {
            $mails[] = $mail;

            return true;
        });

        $mails[0]->assertHasTo($targetUser->email);
        $mails[0]->assertHasCc($otherUser->email);
        $mails[0]->assertHasCc($targetUser->email);
        $mails[0]->assertHasCc('responsible_one@gmail.com');
        $mails[0]->assertHasCc('responsible_two@gmail.com');
        $mails[0]->assertHasCc('foo@cc.com');

        $mails[0]->assertHasBcc($otherUser->email);
        $mails[0]->assertHasBcc($targetUser->email);
        $mails[0]->assertHasBcc('responsible_one@gmail.com');
        $mails[0]->assertHasBcc('responsible_two@gmail.com');
        $mails[0]->assertHasBcc('foo@bcc.com');
    }

    public function test_event_listener_with_static_from_mailable_success()
    {
        $targetUser = User::factory()->create();
        $otherUser = User::factory()->create();
        $company = Company::factory()->create();

        // create event listener for CompanyRegistered event
        EventListener::factory()->genericRegistrationCompany()->create();
        $defaultSetting = DefaultSetting::firstOrFail();
        $settings = $defaultSetting->settings;
        $settings['from'] = [
            'static' => [
                'mailable' => ['from_type' => 'user', 'from_id' => $otherUser->id],
            ],
        ];
        $defaultSetting->settings = $settings;
        $defaultSetting->save();

        Queue::fake();
        Mail::fake();

        CompanyRegistered::dispatch($company, $targetUser);

        Queue::assertNothingPushed();

        $mails = [];
        Mail::assertSent(Custom::class, 1);
        Mail::assertSent(Custom::class, function (Custom $mail) use (&$mails) {
            $mails[] = $mail;

            return true;
        });

        $mails[0]->assertHasTo($targetUser->email);
        $mails[0]->assertFrom($otherUser->email);
    }

    public function test_event_listener_with_static_from_email_success()
    {
        $targetUser = User::factory()->create();
        $company = Company::factory()->create();

        // create event listener for CompanyRegistered event
        EventListener::factory()->genericRegistrationCompany()->create();
        $defaultSetting = DefaultSetting::firstOrFail();
        $settings = $defaultSetting->settings;
        $settings['from'] = [
            'static' => [
                'email' => 'foo@cc.com',
            ],
        ];
        $defaultSetting->settings = $settings;
        $defaultSetting->save();

        Queue::fake();
        Mail::fake();

        CompanyRegistered::dispatch($company, $targetUser);

        Queue::assertNothingPushed();

        $mails = [];
        Mail::assertSent(Custom::class, 1);
        Mail::assertSent(Custom::class, function (Custom $mail) use (&$mails) {
            $mails[] = $mail;

            return true;
        });

        $mails[0]->assertHasTo($targetUser->email);
        $mails[0]->assertFrom('foo@cc.com');
    }

    public function test_event_listener_with_bindings_from_mailable_success()
    {
        $targetUser = User::factory()->create();
        $company = Company::factory()->create();

        // create event listener for CompanyRegistered event
        EventListener::factory()->genericRegistrationCompany()->create();
        $defaultSetting = DefaultSetting::firstOrFail();
        $settings = $defaultSetting->settings;
        $settings['from'] = [
            'bindings' => [
                'mailable' => 'user',
            ],
        ];
        $defaultSetting->settings = $settings;
        $defaultSetting->save();

        Queue::fake();
        Mail::fake();

        CompanyRegistered::dispatch($company, $targetUser);

        Queue::assertNothingPushed();

        $mails = [];
        Mail::assertSent(Custom::class, 1);
        Mail::assertSent(Custom::class, function (Custom $mail) use (&$mails) {
            $mails[] = $mail;

            return true;
        });

        $mails[0]->assertHasTo($targetUser->email);
        $mails[0]->assertFrom($targetUser->email);
    }

    public function test_event_listener_with_bindings_from_email_failure()
    {
        $targetUser = User::factory()->create();
        $company = Company::factory()->create();

        // create event listener for CompanyRegistered event
        EventListener::factory()->genericRegistrationCompany()->create();
        $defaultSetting = DefaultSetting::firstOrFail();
        $settings = $defaultSetting->settings;
        $settings['from'] = [
            'bindings' => [
                'email' => 'responsibles.*.email',
            ],
        ];
        $defaultSetting->settings = $settings;
        $defaultSetting->save();

        Queue::fake();
        Mail::fake();

        /** @var EventActionError $event */
        $event = null;
        Event::listen(function (EventActionError $eventActionError) use (&$event) {
            $event = $eventActionError;
        });

        CompanyRegistered::dispatch($company, $targetUser);

        $this->assertNotNull($event, 'event EventActionError not dispatched');
        $this->assertStringContainsString(
            "several 'from' defined",
            $event->th->getMessage(),
        );
    }
}
