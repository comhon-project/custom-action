<?php

namespace Tests\Unit;

use Carbon\Carbon;
use Comhon\CustomAction\Actions\SendTemplatedMail;
use Comhon\CustomAction\Contracts\CustomUniqueActionInterface;
use Comhon\CustomAction\Mail\Custom;
use Comhon\CustomAction\Models\CustomActionSettings;
use Comhon\CustomAction\Resolver\ModelResolverContainer;
use Comhon\CustomAction\Tests\Support\CompanyRegistered;
use Comhon\CustomAction\Tests\Support\Models\User;
use Comhon\CustomAction\Tests\Support\SendCompanyRegistrationMail;
use Comhon\CustomAction\Tests\TestCase;
use Illuminate\Support\Facades\Mail;

class CustomActionTest extends TestCase
{
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

    public function getSendMailAction(): SendTemplatedMail
    {
        return app(SendTemplatedMail::class);
    }

    private function getSendMailUniqueAction() : SendCompanyRegistrationMail {
        return app(SendCompanyRegistrationMail::class);
    }

    public function testHandleFromNotUniqueAction()
    {
        $this->expectExceptionMessage('must be called from an instance of '.CustomUniqueActionInterface::class);
        $this->getSendMailAction()->handle([]);
    }

    public function testSendWithUserPreferences()
    {
        $user = User::factory(null, ['preferred_locale' => 'fr', 'preferred_timezone' => 'Europe/Paris'])->create();
        $subject = "test {{ datetime|format_datetime('full', 'full', timezone=preferred_timezone) }} test";
        $replacements = ['datetime' => Carbon::parse('2022-12-12T12:12:12Z')];

        Mail::fake();
        $this->getSendMailAction()->send(['subject' => $subject, 'body' => 'word'], $user, $replacements);
        Mail::assertSentCount(1);

        $mails = [];
        Mail::assertSent(Custom::class, function (Custom $mail) use (&$mails) {
            $mails[] = $mail;
            return true;
        });

        $mails[0]->assertHasSubject("test lundi 12 décembre 2022 à 13:12:12 heure normale d’Europe centrale test");
    }

    public function testHandleWithoutReceiver()
    {
        CustomActionSettings::factory()->sendMailRegistrationCompany([], false, true, false)->create();

        $this->expectExceptionMessage('mail receiver is not defined');
        $this->getSendMailUniqueAction()->handle([]);
    }
}
