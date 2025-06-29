<?php

namespace Tests\Feature\SendEmail;

use App\Actions\SendAttachedEmail;
use App\Actions\SendManualSimpleEmail;
use App\Models\User;
use App\Models\UserWithoutPreference;
use Comhon\CustomAction\Actions\Email\AbstractSendManualEmail;
use Comhon\CustomAction\Actions\Email\Mailable\Custom;
use Comhon\CustomAction\Files\SystemFile;
use Comhon\CustomAction\Models\DefaultSetting;
use Comhon\CustomAction\Models\LocalizedSetting;
use Comhon\CustomAction\Models\ManualAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\SetUpWithModelRegistrationTrait;
use Tests\Support\Utils;
use Tests\TestCase;

class HandleManualActionTest extends TestCase
{
    use RefreshDatabase;
    use SetUpWithModelRegistrationTrait;

    private function getAssetPath(): string
    {
        return Utils::joinPaths(Utils::getTestPath('Data'), 'jc.jpeg');
    }

    private function storeSendAttachedEmailAction($twoRecipients)
    {
        $mailables = $twoRecipients ? ['user', 'otherUser'] : ['user'];

        return ManualAction::factory()->action(SendAttachedEmail::class)->withDefaultSettings(
            [
                'recipients' => ['to' => ['context' => ['mailables' => $mailables]]],
                'attachments' => ['logo'],
            ],
            [
                'en' => [
                    'subject' => 'subject {{ to.first_name }} {{ preferred_timezone }}',
                    'body' => 'body {{ to.first_name }} {{ user.translation.translate() }}',
                ],
                'fr' => [
                    'subject' => 'sujet {{ to.first_name }} {{ preferred_timezone }}',
                    'body' => 'corp {{ to.first_name }} {{ user.translation.translate() }}',
                ],
            ]
        )->create();
    }

    public function test_several_recipients_with_different_locales_success()
    {
        $user = User::factory(['preferred_locale' => 'en', 'preferred_timezone' => 'UTC'])->create();
        $otherUser = User::factory(['preferred_locale' => 'fr', 'preferred_timezone' => 'Europe/Paris'])->create();

        $this->storeSendAttachedEmailAction(true);

        Mail::fake();

        SendAttachedEmail::dispatch(new SystemFile($this->getAssetPath()), $user, $otherUser);

        Mail::assertSent(Custom::class, 2);

        /** @var Custom[] $mails */
        $mails = [];
        Mail::assertSent(Custom::class, function (Custom $mail) use (&$mails) {
            return $mails[] = $mail;
        });

        $mails[0]->assertHasTo($user);
        $mails[0]->assertHasSubject("subject $user->first_name UTC");
        $mails[0]->assertSeeInHtml("body $user->first_name foo en");
        $mails[0]->assertHasAttachment(Attachment::fromPath($this->getAssetPath()));

        $mails[1]->assertHasTo($otherUser);
        $mails[1]->assertHasSubject("sujet $otherUser->first_name Europe/Paris");
        $mails[1]->assertSeeInHtml("corp $otherUser->first_name foo fr");
        $mails[1]->assertHasAttachment(Attachment::fromPath($this->getAssetPath()));
    }

    #[DataProvider('providerHandleManualActionSuccess')]
    public function test_fallback_locale_with_model_with_locale_preference($preferredLocale, $appLocale, $fallbackLocale, $success)
    {
        App::setLocale($appLocale);
        App::setFallbackLocale($fallbackLocale);
        $user = User::factory(['preferred_locale' => $preferredLocale])->create();

        $this->storeSendAttachedEmailAction(false);

        Mail::fake();

        if (! $success) {
            $this->expectExceptionMessage('Localized setting for locale \'es\' not found');
        }
        SendAttachedEmail::dispatch(new SystemFile($this->getAssetPath()), $user);

        Mail::assertSent(Custom::class, 1);
        Mail::assertSent(Custom::class, function (Custom $mail) use ($user) {
            return $mail->assertHasTo($user->email)
                ->assertHasSubject("subject $user->first_name UTC");
        });
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
    public function test_fallback_locale_used_with_model_without_locale_preference($appLocale, $fallbackLocale, $success)
    {
        App::setLocale($appLocale);
        App::setFallbackLocale($fallbackLocale);
        $user = UserWithoutPreference::factory()->create();

        $this->storeSendAttachedEmailAction(false);

        Mail::fake();

        if (! $success) {
            $this->expectExceptionMessage('Localized setting not found');
        }
        SendAttachedEmail::dispatch(new SystemFile($this->getAssetPath()), $user);

        Mail::assertSent(Custom::class, 1);
        Mail::assertSent(Custom::class, function (Custom $mail) use ($user) {
            return $mail->assertHasTo($user->email)
                ->assertHasSubject("subject $user->first_name UTC");
        });
    }

    public static function providerHandleManualActionUserWithoutPreferencesSuccess()
    {
        return [
            ['en', 'fr', true],
            ['es', 'en', true],
            ['es', 'es', false],
        ];
    }

    public function test_handle_manual_action_with_grouped_recipients_success()
    {

        $user = User::factory(['preferred_locale' => 'en'])->create();
        $otherUser = User::factory(['preferred_locale' => 'fr'])->create();

        $this->storeSendAttachedEmailAction(true);

        Mail::fake();

        SendAttachedEmail::dispatch(
            new SystemFile($this->getAssetPath()),
            $user,
            $otherUser,
            true,
        );

        Mail::assertSent(Custom::class, 1);
        Mail::assertSent(Custom::class, function (Custom $mail) use ($user, $otherUser) {
            return $mail->assertHasTo($user)->assertHasTo($otherUser);
        });
    }

    #[DataProvider('provider_register_global_timezone_success')]
    public function test_register_global_timezone_success($groupedTimezone)
    {
        AbstractSendManualEmail::registerGroupedTimeZone($groupedTimezone);

        $user = User::factory(['preferred_locale' => 'en'])->create();
        $otherUser = User::factory(['preferred_locale' => 'fr'])->create();

        $this->storeSendAttachedEmailAction(true);

        Mail::fake();

        SendAttachedEmail::dispatch(
            new SystemFile($this->getAssetPath()),
            $user,
            $otherUser,
            true,
        );

        Mail::assertSent(Custom::class, 1);
        Mail::assertSent(Custom::class, function (Custom $mail) use ($groupedTimezone) {
            if ($groupedTimezone instanceof \Closure) {
                $groupedTimezone = $groupedTimezone();
            }
            $groupedTimezone ??= 'UTC';

            return $mail->assertHasSubject("subject  $groupedTimezone");
        });

        AbstractSendManualEmail::registerGroupedTimeZone(null);
    }

    public static function provider_register_global_timezone_success()
    {
        return [
            [null],
            ['UTC'],
            ['Europe/Paris'],
            [fn () => 'UTC'],
            [fn () => 'Europe/Paris'],
        ];
    }

    public function test_send_email_without_recipient()
    {
        $this->storeSendAttachedEmailAction(true);

        $this->expectExceptionMessage('there is no mail recipients defined');
        SendAttachedEmail::dispatch(new SystemFile($this->getAssetPath()));
    }

    #[DataProvider('providerBoolean')]
    public function test_send_manual_email_inject_values_success($fromConstructor)
    {
        if ($fromConstructor) {
            AbstractSendManualEmail::registerDefaultTimeZone(fn () => 'UTC');
        } else {
            AbstractSendManualEmail::registerDefaultTimeZone('UTC');
        }
        ManualAction::factory(['type' => 'send-manual-simple-email'])
            ->has(
                DefaultSetting::factory([
                    'settings' => [],
                ])->has(
                    LocalizedSetting::factory([
                        'locale' => 'en',
                        'settings' => [],
                    ]),
                    'localizedSettings',
                ),
                'defaultSetting'
            )->create();

        Mail::fake();

        if ($fromConstructor) {
            SendManualSimpleEmail::dispatch(
                'to@gmail.com',
                'cc@gmail.com',
                'bcc@gmail.com',
                'from@gmail.com',
                [new SystemFile($this->getAssetPath())],
                'subject',
                'body',
                true,
            );
        } else {
            (new SendManualSimpleEmail)->to('to@gmail.com')
                ->cc('cc@gmail.com')
                ->bcc('bcc@gmail.com')
                ->from('from@gmail.com')
                ->attachments(new SystemFile($this->getAssetPath()))
                ->attachments([new SystemFile($this->getAssetPath())])
                ->subject('subject')
                ->body('body')
                ->groupRecipients(true)
                ->handle();
        }

        /** @var Custom $mail */
        $mail = null;
        Mail::assertSent(Custom::class, 1);
        Mail::assertSent(Custom::class, function (Custom $custom) use (&$mail) {
            $mail = $custom;

            return true;
        });

        $this->assertTrue($mail->hasFrom('from@gmail.com'));
        $mail->assertHasTo('to@gmail.com');
        $mail->assertHasCc('cc@gmail.com');
        $mail->assertHasBcc('bcc@gmail.com');
        $mail->assertHasSubject('subject');
        $mail->assertSeeInHtml('body');
        $mail->assertHasAttachment(Attachment::fromPath($this->getAssetPath()));
        $mail->assertHasAttachment(Attachment::fromPath($this->getAssetPath()));

        AbstractSendManualEmail::registerDefaultTimeZone(null);
    }
}
