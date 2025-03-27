<?php

namespace Tests;

use App\Actions\BadAction;
use App\Actions\MyActionWithoutBindings;
use App\Actions\MyManualActionWithoutBindings;
use App\Actions\SendAutomaticCompanyRegistrationMail;
use App\Actions\SendManualCompanyRegistrationMail;
use App\Actions\SendManualCompanyRegistrationMailWithBindingsTranslations;
use App\Events\BadEvent;
use App\Events\CompanyRegistered;
use App\Events\CompanyRegisteredWithBindingsTranslations;
use App\Events\MyEventWithoutBindings;
use App\Models\Company;
use App\Models\User;
use Comhon\CustomAction\Actions\QueueAutomaticEmail;
use Comhon\CustomAction\Actions\SendAutomaticEmail;
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
            'company-registered-with-bindings-translations' => CompanyRegisteredWithBindingsTranslations::class,
            'send-automatic-email' => SendAutomaticEmail::class,
            'queue-automatic-email' => QueueAutomaticEmail::class,
            'send-automatic-company-email' => SendAutomaticCompanyRegistrationMail::class,
            'send-manual-company-email' => SendManualCompanyRegistrationMail::class,
            'send-manual-company-email-with-bindings-translations' => SendManualCompanyRegistrationMailWithBindingsTranslations::class,

            'my-event-without-bindings' => MyEventWithoutBindings::class,
            'my-action-without-bindings' => MyActionWithoutBindings::class,
            'my-manual-action-without-bindings' => MyManualActionWithoutBindings::class,

            'bad-event' => BadEvent::class,
            'bad-action' => BadAction::class,
        ]);
    }
}
