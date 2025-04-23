<?php

namespace Tests;

use App\Actions\BadAction;
use App\Actions\MyActionWithoutContext;
use App\Actions\MyManualActionWithoutContext;
use App\Actions\SendAutomaticCompanyRegistrationMail;
use App\Actions\SendManualCompanyRegistrationGroupedMail;
use App\Actions\SendManualCompanyRegistrationMail;
use App\Actions\SendManualCompanyRegistrationMailWithContextTranslations;
use App\Actions\SendManualUserRegisteredEmail;
use App\Events\BadEvent;
use App\Events\CompanyRegistered;
use App\Events\CompanyRegisteredWithContextTranslations;
use App\Events\MyEventWithoutContext;
use App\Models\Company;
use App\Models\User;
use Comhon\CustomAction\Actions\Email\QueueAutomaticEmail;
use Comhon\CustomAction\Actions\Email\SendAutomaticEmail;
use Comhon\CustomAction\Facades\CustomActionModelResolver;

trait SetUpWithModelRegistrationTrait
{
    public function setUp(): void
    {
        parent::setUp();

        CustomActionModelResolver::register([
            'user' => User::class,
            'company' => Company::class,

            'company-registered' => CompanyRegistered::class,
            'company-registered-with-context-translations' => CompanyRegisteredWithContextTranslations::class,
            'send-automatic-email' => SendAutomaticEmail::class,
            'queue-automatic-email' => QueueAutomaticEmail::class,
            'send-automatic-company-email' => SendAutomaticCompanyRegistrationMail::class,
            'send-manual-company-email' => SendManualCompanyRegistrationMail::class,
            'send-manual-company-email-with-context-translations' => SendManualCompanyRegistrationMailWithContextTranslations::class,
            'send-manual-company-grouped-email' => SendManualCompanyRegistrationGroupedMail::class,
            'send-manual-user-registered-email' => SendManualUserRegisteredEmail::class,

            'my-event-without-context' => MyEventWithoutContext::class,
            'my-action-without-context' => MyActionWithoutContext::class,
            'my-manual-action-without-context' => MyManualActionWithoutContext::class,

            'bad-event' => BadEvent::class,
            'bad-action' => BadAction::class,
        ]);
    }
}
