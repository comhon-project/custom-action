<?php

namespace Tests\Feature;

use App\Events\CompanyRegistered;
use App\Events\MyEventWithoutBindings;
use App\Models\Company;
use App\Models\User;
use Comhon\CustomAction\Actions\QueueTemplatedMail;
use Comhon\CustomAction\Mail\Custom;
use Comhon\CustomAction\Models\ActionLocalizedSettings;
use Comhon\CustomAction\Models\ActionSettings;
use Comhon\CustomAction\Models\EventAction;
use Comhon\CustomAction\Models\EventListener;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Mockery\MockInterface;
use Tests\SetUpWithModelRegistrationTrait;
use Tests\Support\Caller;
use Tests\Support\Utils;
use Tests\TestCase;

class EventDispatchTest extends TestCase
{
    use RefreshDatabase;
    use SetUpWithModelRegistrationTrait;

    /**
     * @dataProvider providerEventListener
     *
     * @return void
     */
    public function testEventListenerSuccess($addCompanyScope)
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

    public function testEventListenerWithEventWithoutBindings()
    {
        // create event listener for CompanyRegistered event
        $settings = ActionSettings::factory()
            ->withEventActionType('my-action-without-bindings')
            ->create();

        $eventListener = $settings->eventAction->eventListener;
        $eventListener->event = 'my-event-without-bindings';
        $eventListener->save();

        $this->partialMock(Caller::class, function (MockInterface $mock) use ($settings) {
            $mock->shouldReceive('call')->once()->withArgs(function ($actionSettings, $bindingsContainer) use ($settings) {
                return $settings->is($actionSettings) && $bindingsContainer === null;
            });
        });

        MyEventWithoutBindings::dispatch();
    }

    public function testEventListenerWithAllAvailableToProperties()
    {
        /** @var User $user */
        $user = User::factory()->create();
        $companyName = 'my company';
        $company = Company::factory(['name' => $companyName])->create();

        $listener = EventListener::factory(['event' => 'company-registered'])
            ->create();

        $receiver = User::factory(['preferred_locale' => 'fr'])->create();
        $actionSettings = ActionSettings::factory([
            'settings' => [
                'to_receivers' => [['receiver_type' => 'user', 'receiver_id' => $receiver->id]],
                'to_emails' => ['john.doe@gmail.com'],
                'to_bindings_receivers' => ['user'],
                'to_bindings_emails' => ['responsibles.*.email'],
            ],
        ])->create();
        EventAction::factory()
            ->for($listener, 'eventListener')
            ->for($actionSettings, 'actionSettings')
            ->create();

        ActionLocalizedSettings::factory()->for($actionSettings, 'localizable')->emailSettings('en')->create();
        ActionLocalizedSettings::factory()->for($actionSettings, 'localizable')->emailSettings('fr')->create();

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
            $mail->assertHasSubject("Cher·ère $user->first_name, société VIP $company->name fr (vérifié à: 11 novembre 2022 (UTC))");
        } else {
            $mail->assertHasSubject("Dear $user->first_name, VIP company $company->name en (verified at: November 11, 2022 (UTC))");
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

        Queue::fake();
        CompanyRegistered::dispatch($company, $targetUser);

        Queue::assertPushed(QueueTemplatedMail::class, 2);
    }
}
