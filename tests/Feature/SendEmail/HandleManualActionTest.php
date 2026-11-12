<?php

namespace Tests\Feature\SendEmail;

use App\Actions\SendAttachedEmail;
use App\Actions\SendManualSimpleEmail;
use App\Models\User;
use App\Models\UserWithoutPreference;
use Comhon\CustomAction\Actions\Email\AbstractSendManualEmail;
use Comhon\CustomAction\Actions\Email\Exceptions\CustomMailableException;
use Comhon\CustomAction\Actions\Email\Mailable\Custom;
use Comhon\CustomAction\Exceptions\LocalizedSettingNotFoundException;
use Comhon\CustomAction\Files\StorageFile;
use Comhon\CustomAction\Files\SystemFile;
use Comhon\CustomAction\Models\DefaultSetting;
use Comhon\CustomAction\Models\LocalizedSetting;
use Comhon\CustomAction\Models\ManualAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Mime\Exception\RfcComplianceException;
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

    private function storeSendManualSimpleEmailAction(): void
    {
        ManualAction::factory(['type' => 'send-manual-simple-email'])
            ->has(
                DefaultSetting::factory([
                    'settings' => [],
                ])->has(
                    LocalizedSetting::factory([
                        'locale' => 'en',
                        'settings' => ['subject' => 'subject', 'body' => 'body'],
                    ]),
                    'localizedSettings',
                ),
                'defaultSetting'
            )->create();
    }

    public function test_send_after_commit_sends_mail_once_transaction_is_committed()
    {
        $this->storeSendManualSimpleEmailAction();
        Mail::fake();

        DB::transaction(function () {
            (new SendManualSimpleEmail)->to('to@gmail.com')->sendAfterCommit()->handle();

            Mail::assertNothingSent();
        });

        Mail::assertSent(Custom::class, 1);
        Mail::assertSent(Custom::class, fn (Custom $mail) => $mail->hasTo('to@gmail.com'));
    }

    public function test_send_after_commit_from_dispatch_sends_mail_once_transaction_is_committed()
    {
        $this->storeSendManualSimpleEmailAction();
        Mail::fake();

        DB::transaction(function () {
            SendManualSimpleEmail::dispatch('to@gmail.com')->sendAfterCommit();

            Mail::assertNothingSent();
        });

        Mail::assertSent(Custom::class, 1);
    }

    public function test_send_after_commit_doesnt_send_mail_if_transaction_is_rolled_back()
    {
        $this->storeSendManualSimpleEmailAction();
        Mail::fake();

        DB::beginTransaction();
        (new SendManualSimpleEmail)->to('to@gmail.com')->sendAfterCommit()->handle();
        DB::rollBack();

        Mail::assertNothingSent();
    }

    public function test_send_after_commit_sends_mail_immediately_without_transaction()
    {
        $this->storeSendManualSimpleEmailAction();
        Mail::fake();

        (new SendManualSimpleEmail)->to('to@gmail.com')->sendAfterCommit()->handle();

        Mail::assertSent(Custom::class, 1);
    }

    public function test_send_after_commit_disabled_sends_mail_inside_transaction()
    {
        $this->storeSendManualSimpleEmailAction();
        Mail::fake();

        DB::transaction(function () {
            (new SendManualSimpleEmail)->to('to@gmail.com')->sendAfterCommit()->sendAfterCommit(false)->handle();

            Mail::assertSent(Custom::class, 1);
        });
    }

    #[DataProvider('providerBoolean')]
    public function test_build_failure_with_several_recipients($sendAfterCommit)
    {
        App::setLocale('es');
        App::setFallbackLocale('es');
        $user = User::factory(['preferred_locale' => 'en'])->create();
        $otherUser = User::factory(['preferred_locale' => 'es'])->create();

        $this->storeSendManualSimpleEmailAction();
        Mail::fake();

        $thrown = false;
        try {
            DB::transaction(function () use ($user, $otherUser, $sendAfterCommit) {
                (new SendManualSimpleEmail)->to([$user, $otherUser])->sendAfterCommit($sendAfterCommit)->handle();
            });
        } catch (LocalizedSettingNotFoundException) {
            $thrown = true;
        }

        $this->assertTrue($thrown);
        if ($sendAfterCommit) {
            // the mail of the first recipient is discarded with the rolled back transaction
            Mail::assertNothingSent();
        } else {
            // mails are sent one by one, the mail of the first recipient is already sent
            Mail::assertSent(Custom::class, 1);
        }
    }

    #[DataProvider('providerSendAfterCommitBuildFailure')]
    public function test_send_after_commit_build_failure_happens_inside_transaction(\Closure $configure, string $exceptionClass)
    {
        Storage::fake('local');
        $this->storeSendManualSimpleEmailAction();
        Mail::fake();
        $usersCount = User::count();

        $thrown = null;
        try {
            DB::transaction(function () use ($configure) {
                User::factory()->create();
                $configure((new SendManualSimpleEmail)->to('to@gmail.com'))->sendAfterCommit()->handle();
            });
        } catch (\Throwable $th) {
            $thrown = $th;
        }

        $this->assertInstanceOf($exceptionClass, $thrown);
        $this->assertEquals($usersCount, User::count(), 'transaction should be rolled back');
        Mail::assertNothingSent();
    }

    public static function providerSendAfterCommitBuildFailure()
    {
        return [
            'template rendering' => [
                fn (SendManualSimpleEmail $action) => $action->body('{{ unclosed'),
                \Throwable::class, // the exception class depends on the template renderer driver
            ],
            'missing file attachment' => [
                fn (SendManualSimpleEmail $action) => $action->attachments(new SystemFile('/not/existing/file.pdf')),
                CustomMailableException::class,
            ],
            'missing storage attachment' => [
                fn (SendManualSimpleEmail $action) => $action->attachments(new StorageFile('not-existing.pdf', 'local')),
                CustomMailableException::class,
            ],
            'invalid address' => [
                fn (SendManualSimpleEmail $action) => $action->to('not-an-email'),
                RfcComplianceException::class,
            ],
        ];
    }

    public function test_send_after_commit_loads_storage_attachment_inside_transaction()
    {
        Storage::fake('local');
        Storage::disk('local')->put('file.txt', 'file content');
        $this->storeSendManualSimpleEmailAction();
        Mail::fake();

        DB::transaction(function () {
            (new SendManualSimpleEmail)->to(['to@gmail.com', 'other@gmail.com'])
                ->attachments(new StorageFile('file.txt', 'local'))
                ->sendAfterCommit()
                ->handle();

            // the content is already loaded, so the file is not needed anymore
            Storage::disk('local')->delete('file.txt');
        });

        Mail::assertSent(Custom::class, 2);
        Mail::assertSent(Custom::class, fn (Custom $mail) => $mail->hasAttachment(
            Attachment::fromData(fn () => 'file content', 'file.txt')->withMime('text/plain')
        ));
    }
}
