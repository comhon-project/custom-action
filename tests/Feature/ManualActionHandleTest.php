<?php

namespace Tests\Feature;

use App\Actions\SendCompanyRegistrationMail;
use App\Models\Company;
use App\Models\User;
use App\Models\UserWithoutPreference;
use Comhon\CustomAction\Bindings\BindingsContainer;
use Comhon\CustomAction\Files\SystemFile;
use Comhon\CustomAction\Mail\Custom;
use Comhon\CustomAction\Models\ManualAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;
use Tests\SetUpWithModelRegistration;
use Tests\Support\Utils;
use Tests\TestCase;

class ManualActionHandleTest extends TestCase
{
    use RefreshDatabase;
    use SetUpWithModelRegistration;

    private function getAssetPath(): string
    {
        return Utils::joinPaths(Utils::getTestPath('Data'), 'jc.jpeg');
    }

    /**
     * @dataProvider providerHandleManualActionSuccess
     */
    public function testHandleManualActionSuccess($preferredLocale, $appLocale, $fallbackLocale, $success)
    {
        App::setLocale($appLocale);
        App::setFallbackLocale($fallbackLocale);
        $user = User::factory(['preferred_locale' => $preferredLocale])->create();
        $company = Company::factory()->create();
        ManualAction::factory()->sendMailRegistrationCompany(null, false, true)->create();

        $bindings = ['company' => $company, 'logo' => new SystemFile($this->getAssetPath())];

        Mail::fake();

        if (! $success) {
            $this->expectExceptionMessage('localized mail values not found');
        }
        SendCompanyRegistrationMail::handleManual(new BindingsContainer($bindings), $user);

        $mails = [];
        Mail::assertSent(Custom::class, 1);
        Mail::assertSent(Custom::class, function (Custom $mail) use (&$mails) {
            $mails[] = $mail;

            return true;
        });
        $mails[0]->assertHasTo($user->email);
        $mails[0]->assertHasSubject(
            "Dear $user->first_name, company $company->name  (login: December 12, 2022 at 12:00 AM (UTC) December 12, 2022 at 12:00 AM (UTC))"
        );
        $this->assertTrue($mails[0]->hasAttachment(Attachment::fromPath($this->getAssetPath())));
    }

    public static function providerHandleManualActionSuccess()
    {
        return [
            ['en', 'fr', 'fr', true],
            ['es', 'en', 'fr', true],
            ['es', 'es', 'en', true],
            ['es', 'es', 'es', false],
        ];
    }

    /**
     * @dataProvider providerHandleManualActionUserWithoutPreferencesSuccess
     */
    public function testHandleManualActionUserWithoutPreferencesSuccess($appLocale, $fallbackLocale, $success)
    {
        App::setLocale($appLocale);
        App::setFallbackLocale($fallbackLocale);
        $user = UserWithoutPreference::factory()->create();
        $company = Company::factory()->create();
        ManualAction::factory()->sendMailRegistrationCompany(null, false, true)->create();

        $bindings = ['company' => $company, 'logo' => new SystemFile($this->getAssetPath())];

        Mail::fake();

        if (! $success) {
            $this->expectExceptionMessage('localized mail values not found');
        }
        SendCompanyRegistrationMail::handleManual(new BindingsContainer($bindings), $user);

        $mails = [];
        Mail::assertSent(Custom::class, 1);
        Mail::assertSent(Custom::class, function (Custom $mail) use (&$mails) {
            $mails[] = $mail;

            return true;
        });
        $mails[0]->assertHasTo($user->email);
        $mails[0]->assertHasSubject(
            "Dear $user->first_name, company $company->name  (login: December 12, 2022 at 12:00 AM (UTC) December 12, 2022 at 12:00 AM (UTC))"
        );
        $this->assertTrue($mails[0]->hasAttachment(Attachment::fromPath($this->getAssetPath())));
    }

    /**
     * @dataProvider providerHandleManualActionUserWithoutPreferencesSuccess
     */
    public function testHandleManualActionUserWithBindingsContainerWithSchemaAllValid($appLocale, $fallbackLocale, $success)
    {
        App::setLocale($appLocale);
        App::setFallbackLocale($fallbackLocale);
        $user = UserWithoutPreference::factory()->create();
        $company = Company::factory()->create();
        ManualAction::factory()->sendMailRegistrationCompany(null, false, true)->create();

        $bindings = ['company' => $company, 'logo' => new SystemFile($this->getAssetPath())];
        $bindingSchema = [
            'company' => 'is:company',
            'logo' => 'is:stored-file',
        ];

        Mail::fake();

        if (! $success) {
            $this->expectExceptionMessage('localized mail values not found');
        }
        SendCompanyRegistrationMail::handleManual(new BindingsContainer($bindings, $bindingSchema), $user);

        $mails = [];
        Mail::assertSent(Custom::class, 1);
        Mail::assertSent(Custom::class, function (Custom $mail) use (&$mails) {
            $mails[] = $mail;

            return true;
        });
        $mails[0]->assertHasTo($user->email);
        $mails[0]->assertHasSubject(
            "Dear $user->first_name, company $company->name  (login: December 12, 2022 at 12:00 AM (UTC) December 12, 2022 at 12:00 AM (UTC))"
        );
        $this->assertTrue($mails[0]->hasAttachment(Attachment::fromPath($this->getAssetPath())));
    }

    public function testHandleManualActionUserWithBindingsContainerWithSchemaWithInvalid()
    {
        $user = UserWithoutPreference::factory()->create();
        ManualAction::factory()->sendMailRegistrationCompany(null, false, true)->create();

        $bindings = ['company' => $user, 'logo' => new SystemFile($this->getAssetPath())];
        $bindingSchema = [
            'company' => 'is:company',
            'logo' => 'is:stored-file',
        ];

        $this->expectExceptionMessage('The company is not instance of company.');
        SendCompanyRegistrationMail::handleManual(new BindingsContainer($bindings, $bindingSchema), $user);

    }

    /**
     * @dataProvider providerHandleManualActionUserWithoutPreferencesSuccess
     */
    public function testHandleManualActionUserWithoutBindingsContainer($appLocale, $fallbackLocale, $success)
    {
        App::setLocale($appLocale);
        App::setFallbackLocale($fallbackLocale);
        $user = UserWithoutPreference::factory()->create();
        $company = Company::factory()->create();
        ManualAction::factory()->sendMailRegistrationCompany(null, false, true)->create();

        Mail::fake();

        if (! $success) {
            $this->expectExceptionMessage('localized mail values not found');
        }
        SendCompanyRegistrationMail::handleManual(null, $user);

        $mails = [];
        Mail::assertSent(Custom::class, 1);
        Mail::assertSent(Custom::class, function (Custom $mail) use (&$mails) {
            $mails[] = $mail;

            return true;
        });
        $mails[0]->assertHasTo($user->email);
        $mails[0]->assertHasSubject(
            "Dear $user->first_name, company   (login: December 12, 2022 at 12:00 AM (UTC) December 12, 2022 at 12:00 AM (UTC))"
        );
    }

    public static function providerHandleManualActionUserWithoutPreferencesSuccess()
    {
        return [
            ['en', 'fr', true],
            ['es', 'en', true],
            ['es', 'es', false],
        ];
    }

    public function testHandleManualActionWithoutSettings()
    {
        /** @var User $user */
        $user = User::factory()->create();
        $company = Company::factory()->create();

        $bindings = ['company' => $company, 'logo' => new SystemFile($this->getAssetPath())];
        $this->expectExceptionMessage('No query results for model');
        SendCompanyRegistrationMail::handleManual(new BindingsContainer($bindings), $user);
    }

    public function testHandleManualActionWithScopedSettingsConflicts()
    {
        /** @var User $user */
        $user = User::factory()->create();
        $company = Company::factory(['name' => 'My VIP company'])->create();

        $action = ManualAction::factory()->sendMailRegistrationCompany(null, true)->create();
        $action->actionSettings->scopedSettings->first()->replicate()->save();

        $this->expectExceptionMessage('cannot resolve conflict between several action scoped settings');
        SendCompanyRegistrationMail::handleManual(new BindingsContainer(['company' => $company]), $user);

    }
}
