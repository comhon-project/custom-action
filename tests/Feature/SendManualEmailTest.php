<?php

namespace Tests\Feature;

use App\Actions\SendManualUserRegisteredEmail;
use App\Models\User;
use Comhon\CustomAction\Mail\Custom;
use Comhon\CustomAction\Models\DefaultSetting;
use Comhon\CustomAction\Models\LocalizedSetting;
use Comhon\CustomAction\Models\ManualAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\SetUpWithModelRegistrationTrait;
use Tests\TestCase;

class SendManualEmailTest extends TestCase
{
    use RefreshDatabase;
    use SetUpWithModelRegistrationTrait;

    public function test_send_manual_email_success()
    {
        $user = User::factory()->create();

        ManualAction::factory(['type' => 'send-manual-user-registered-email'])
            ->has(
                DefaultSetting::factory([
                    'settings' => [
                        'recipients' => ['to' => ['context' => ['mailables' => ['users.*']]]],
                    ],
                ])->has(
                    LocalizedSetting::factory([
                        'locale' => 'en',
                        'settings' => [
                            'subject' => 'Dear {{ users.0.first_name }}',
                            'body' => 'Dear {{ users.0.first_name }}, you have been registered !',
                        ],
                    ]),
                    'localizedSettings',
                ),
                'defaultSetting'
            )->create();

        Mail::fake();

        SendManualUserRegisteredEmail::dispatch([$user]);

        Mail::assertSent(Custom::class, 1);
        Mail::assertSent(Custom::class, function (Custom $mail) use ($user) {
            return $mail->hasTo($user->email) && $mail->hasSubject("Dear {$user->first_name}");
        });
    }

    public function test_send_manual_email_overridden_success()
    {
        $user = User::factory()->create();

        ManualAction::factory(['type' => 'send-manual-user-registered-email'])
            ->has(
                DefaultSetting::factory([
                    'settings' => [
                        'recipients' => ['to' => ['context' => ['mailables' => ['users.*']]]],
                    ],
                ])->has(
                    LocalizedSetting::factory([
                        'locale' => 'en',
                        'settings' => [
                            'subject' => 'Dear {{ users.0.first_name }}',
                            'body' => 'Dear {{ users.0.first_name }}, you have been registered !',
                        ],
                    ]),
                    'localizedSettings',
                ),
                'defaultSetting'
            )->create();

        Mail::fake();

        SendManualUserRegisteredEmail::dispatch([$user], false, 'foo@bar.com', 'Overridden {{ users.0.first_name }}', 'body');

        Mail::assertSent(Custom::class, 1);
        Mail::assertSent(Custom::class, function (Custom $mail) use ($user) {
            return $mail->hasTo($user->email)
                && $mail->hasSubject("Overridden {$user->first_name}")
                && $mail->hasFrom('foo@bar.com');
        });
    }

    #[DataProvider('providerBoolean')]
    public function test_preview_manual_email_success($grouped)
    {
        $renderTemplate = $grouped;
        $user = User::factory()->create();

        ManualAction::factory(['type' => 'send-manual-user-registered-email'])
            ->has(
                DefaultSetting::factory([
                    'settings' => [
                        'recipients' => ['to' => ['context' => ['mailables' => ['users.*']]]],
                    ],
                ])->has(
                    LocalizedSetting::factory([
                        'locale' => 'en',
                        'settings' => [
                            'subject' => 'Dear {{ users.0.first_name }}',
                            'body' => 'Dear {{ users.0.first_name }}, you have been registered !',
                        ],
                    ]),
                    'localizedSettings',
                ),
                'defaultSetting'
            )->create();

        Mail::fake();

        $users = $grouped ? [$user, User::factory()->create()] : [$user];
        $preview = (new SendManualUserRegisteredEmail($users, $grouped))->preview($renderTemplate);

        Mail::assertSent(Custom::class, 0);
        $expected = $renderTemplate
            ? "Dear {$user->first_name}, you have been registered !"
            : 'Dear {{ users.0.first_name }}, you have been registered !';
        $this->assertEquals($expected, $preview);
    }

    public function test_preview_manual_email_failure()
    {
        ManualAction::factory(['type' => 'send-manual-user-registered-email'])
            ->has(
                DefaultSetting::factory([
                    'settings' => [
                        'recipients' => ['to' => ['context' => ['mailables' => ['users.*']]]],
                    ],
                ])->has(
                    LocalizedSetting::factory([
                        'locale' => 'en',
                        'settings' => [
                            'subject' => 'Dear {{ users.0.first_name }}',
                            'body' => 'Dear {{ users.0.first_name }}, you have been registered !',
                        ],
                    ]),
                    'localizedSettings',
                ),
                'defaultSetting'
            )->create();

        Mail::fake();

        $users = [User::factory()->create(), User::factory()->create()];

        $this->expectExceptionMessage('must have one and only one email to send to generate preview');
        (new SendManualUserRegisteredEmail($users))->preview();
    }
}
