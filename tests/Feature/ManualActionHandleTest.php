<?php

namespace Tests\Feature;

use App\Actions\SendManualCompanyRegistrationMail;
use App\Models\Company;
use App\Models\User;
use App\Models\UserWithoutPreference;
use Comhon\CustomAction\Files\SystemFile;
use Comhon\CustomAction\Mail\Custom;
use Comhon\CustomAction\Models\ManualAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\SetUpWithModelRegistrationTrait;
use Tests\Support\Utils;
use Tests\TestCase;

class ManualActionHandleTest extends TestCase
{
    use RefreshDatabase;
    use SetUpWithModelRegistrationTrait;

    private function getAssetPath(): string
    {
        return Utils::joinPaths(Utils::getTestPath('Data'), 'jc.jpeg');
    }

    #[DataProvider('providerHandleManualActionSuccess')]
    public function test_handle_manual_action_success($preferredLocale, $appLocale, $fallbackLocale, $success)
    {
        App::setLocale($appLocale);
        App::setFallbackLocale($fallbackLocale);
        $user = User::factory(['preferred_locale' => $preferredLocale])->create();
        $company = Company::factory()->create();
        ManualAction::factory()->sendMailRegistrationCompany(null, false, true)->create();

        Mail::fake();

        if (! $success) {
            $this->expectExceptionMessage('Localized setting for locale \'es\' not found');
        }
        SendManualCompanyRegistrationMail::dispatch(
            $company,
            new SystemFile($this->getAssetPath()),
            $user,
        );

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

    #[DataProvider('providerHandleManualActionUserWithoutPreferencesSuccess')]
    public function test_handle_manual_action_user_without_preferences_success($appLocale, $fallbackLocale, $success)
    {
        App::setLocale($appLocale);
        App::setFallbackLocale($fallbackLocale);
        $user = UserWithoutPreference::factory()->create();
        $company = Company::factory()->create();
        ManualAction::factory()->sendMailRegistrationCompany(null, false, true)->create();

        Mail::fake();

        if (! $success) {
            $this->expectExceptionMessage('Localized setting not found');
        }
        SendManualCompanyRegistrationMail::dispatch(
            $company,
            new SystemFile($this->getAssetPath()),
            $user,
        );

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

    public function test_handle_manual_action_user_with_bindings_container_with_schema_with_invalid()
    {
        $user = UserWithoutPreference::factory()->create();
        $company = Company::factory()->create();
        $company->name = ['foo'];
        ManualAction::factory()->sendMailRegistrationCompany(null, false, true)->create();

        $this->expectExceptionMessage('The company.name field must be a string.');
        SendManualCompanyRegistrationMail::dispatch(
            $company,
            new SystemFile($this->getAssetPath()),
            $user,
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

    public function test_handle_manual_action_without_settings()
    {
        /** @var User $user */
        $user = User::factory()->create();
        $company = Company::factory()->create();

        $this->expectExceptionMessage('manual action send-manual-company-email not found');
        SendManualCompanyRegistrationMail::dispatch(
            $company,
            new SystemFile($this->getAssetPath()),
            $user,
        );
    }

    public function test_handle_manual_action_with_scoped_settings_conflicts()
    {
        /** @var User $user */
        $user = User::factory()->create();
        $company = Company::factory(['name' => 'My VIP company'])->create();

        $action = ManualAction::factory()->sendMailRegistrationCompany(null, true)->create();
        $copedSettings = $action->scopedSettings->first()->replicate();
        $copedSettings->name = 'foo';
        $copedSettings->save();

        $this->expectExceptionMessage('cannot resolve conflict between several scoped settings');
        SendManualCompanyRegistrationMail::dispatch(
            $company,
            new SystemFile($this->getAssetPath()),
            $user,
        );
    }
}
