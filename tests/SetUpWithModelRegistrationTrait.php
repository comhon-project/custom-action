<?php

namespace Tests;

use App\Actions\BadAction;
use App\Actions\ComplexEventAction;
use App\Actions\ComplexManualAction;
use App\Actions\FakableNotSimulatabeAction;
use App\Actions\QueuedEventAction;
use App\Actions\QueuedManualAction;
use App\Actions\SendAutomaticCompanyRegistrationMail;
use App\Actions\SendManualCompanyRegistrationGroupedMail;
use App\Actions\SendManualCompanyRegistrationMail;
use App\Actions\SendManualCompanyRegistrationMailWithContextTranslations;
use App\Actions\SendManualSimpleEmail;
use App\Actions\SendManualUserRegisteredEmail;
use App\Actions\SimpleEventAction;
use App\Actions\SimpleManualAction;
use App\Actions\SimulatabeNotFakableAction;
use App\Actions\SimulatabeWithoutMethodAction;
use App\Events\BadEvent;
use App\Events\CompanyRegistered;
use App\Events\CompanyRegisteredWithContextTranslations;
use App\Events\MyComplexEvent;
use App\Events\MySimpleEvent;
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
            'send-manual-simple-email' => SendManualSimpleEmail::class,

            'simple-manual-action' => SimpleManualAction::class,
            'queued-manual-action' => QueuedManualAction::class,
            'complex-manual-action' => ComplexManualAction::class,
            'simple-event-action' => SimpleEventAction::class,
            'queued-event-action' => QueuedEventAction::class,
            'complex-event-action' => ComplexEventAction::class,

            'fakable-not-simulatable-action' => FakableNotSimulatabeAction::class,
            'simulatable-not-fakable-action' => SimulatabeNotFakableAction::class,
            'simulatable-without-method-action' => SimulatabeWithoutMethodAction::class,
            'bad-event' => BadEvent::class,
            'bad-action' => BadAction::class,

            'my-simple-event' => MySimpleEvent::class,
            'my-complex-event' => MyComplexEvent::class,
        ]);
    }
}
