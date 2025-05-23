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
}
