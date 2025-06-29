<?php

namespace Tests\Feature\SendEmail;

use App\Actions\SendAttachedEmail;
use App\Models\User;
use Comhon\CustomAction\Actions\Email\Mailable\Custom;
use Comhon\CustomAction\Files\SystemFile;
use Comhon\CustomAction\Models\ManualAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\SetUpWithModelRegistrationTrait;
use Tests\Support\Utils;
use Tests\TestCase;

class SendGenericEmailSettingsTest extends TestCase
{
    use RefreshDatabase;
    use SetUpWithModelRegistrationTrait;

    private function getAssetPath(): string
    {
        return Utils::joinPaths(Utils::getTestPath('Data'), 'jc.jpeg');
    }

    private function storeSendAttachedEmailAction(array $settings)
    {
        return ManualAction::factory()->action(SendAttachedEmail::class)->withDefaultSettings(
            $settings,
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

    public function test_send_email_with_all_available_to_properties()
    {
        /** @var User $user */
        $user = User::factory()->create();

        $receiver = User::factory()->create();
        $this->storeSendAttachedEmailAction([
            'recipients' => ['to' => [
                'static' => [
                    'mailables' => [
                        ['recipient_type' => 'user', 'recipient_id' => $receiver->id],
                    ],
                    'emails' => ['john.doe@gmail.com'],
                ],
                'context' => [
                    'mailables' => ['user'],
                    'emails' => ['responsibles.*.email'],
                ],
            ]],
        ]);

        Mail::fake();

        SendAttachedEmail::dispatch(new SystemFile($this->getAssetPath()), $user);

        $mails = [];
        Mail::assertSent(Custom::class, 5);
        Mail::assertSent(Custom::class, function (Custom $mail) use (&$mails) {
            $mails[] = $mail;

            return true;
        });

        $mails[0]->assertHasTo($receiver->email);
        $mails[1]->assertHasTo('john.doe@gmail.com');
        $mails[2]->assertHasTo($user->email);
        $mails[3]->assertHasTo('responsible_one@gmail.com');
        $mails[4]->assertHasTo('responsible_two@gmail.com');
    }

    public function test_send_email_with_cc_bcc_success()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $staticUser = User::factory()->create();

        $this->storeSendAttachedEmailAction([
            'recipients' => [
                'to' => ['context' => ['mailables' => ['user']]],
                'cc' => [
                    'static' => [
                        'mailables' => [['recipient_type' => 'user', 'recipient_id' => $staticUser->id]],
                        'emails' => ['foo@cc.com'],
                    ],
                    'context' => [
                        'mailables' => ['otherUser'],
                        'emails' => ['responsibles.*.email'],
                    ],
                ],
                'bcc' => [
                    'static' => [
                        'mailables' => [['recipient_type' => 'user', 'recipient_id' => $staticUser->id]],
                        'emails' => ['foo@bcc.com'],
                    ],
                    'context' => [
                        'mailables' => ['otherUser'],
                        'emails' => ['responsibles.*.email'],
                    ],
                ],
            ],
        ]);

        Mail::fake();

        SendAttachedEmail::dispatch(new SystemFile($this->getAssetPath()), $user, $otherUser);

        Mail::assertSent(Custom::class, 1);
        Mail::assertSent(Custom::class, function (Custom $mail) use ($user, $otherUser, $staticUser) {
            return $mail->assertHasTo($user->email)
                ->assertHasCc($otherUser->email)
                ->assertHasCc($staticUser->email)
                ->assertHasCc('responsible_one@gmail.com')
                ->assertHasCc('responsible_two@gmail.com')
                ->assertHasCc('foo@cc.com')
                ->assertHasBcc($otherUser->email)
                ->assertHasBcc($staticUser->email)
                ->assertHasBcc('responsible_one@gmail.com')
                ->assertHasBcc('responsible_two@gmail.com')
                ->assertHasBcc('foo@bcc.com');
        });
    }

    public function test_send_email_with_static_from_mailable_success()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->storeSendAttachedEmailAction([
            'from' => [
                'static' => [
                    'mailable' => ['from_type' => 'user', 'from_id' => $otherUser->id],
                ],
            ],
            'recipients' => ['to' => ['context' => ['mailables' => ['user']]]],
        ]);

        Mail::fake();

        SendAttachedEmail::dispatch(new SystemFile($this->getAssetPath()), $user);

        Mail::assertSent(Custom::class, 1);
        Mail::assertSent(Custom::class, function (Custom $mail) use ($user, $otherUser) {
            return $mail->assertHasTo($user->email)
                ->assertFrom($otherUser->email);
        });
    }

    public function test_send_email_with_static_from_email_success()
    {
        $user = User::factory()->create();

        $this->storeSendAttachedEmailAction([
            'from' => [
                'static' => [
                    'email' => 'foo@cc.com',
                ],
            ],
            'recipients' => ['to' => ['context' => ['mailables' => ['user']]]],
        ]);

        Mail::fake();

        SendAttachedEmail::dispatch(new SystemFile($this->getAssetPath()), $user);

        Mail::assertSent(Custom::class, 1);
        Mail::assertSent(Custom::class, function (Custom $mail) use ($user) {
            return $mail->assertHasTo($user->email)
                ->assertFrom('foo@cc.com');
        });
    }

    public function test_send_email_with_context_from_mailable_success()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->storeSendAttachedEmailAction([
            'from' => [
                'context' => [
                    'mailable' => 'otherUser',
                ],
            ],
            'recipients' => ['to' => ['context' => ['mailables' => ['user']]]],
        ]);

        Mail::fake();

        SendAttachedEmail::dispatch(new SystemFile($this->getAssetPath()), $user, $otherUser);

        Mail::assertSent(Custom::class, 1);
        Mail::assertSent(Custom::class, function (Custom $mail) use ($user, $otherUser) {
            return $mail->assertHasTo($user->email)
                ->assertFrom($otherUser->email);
        });
    }

    public function test_send_email_with_context_from_email_success()
    {
        $user = User::factory()->create();

        $this->storeSendAttachedEmailAction([
            'from' => [
                'context' => [
                    'email' => 'responsibles.0.email',
                ],
            ],
            'recipients' => ['to' => ['context' => ['mailables' => ['user']]]],
        ]);

        Mail::fake();

        SendAttachedEmail::dispatch(new SystemFile($this->getAssetPath()), $user);

        Mail::assertSent(Custom::class, 1);
        Mail::assertSent(Custom::class, function (Custom $mail) use ($user) {
            return $mail->assertHasTo($user->email)
                ->assertFrom('responsible_one@gmail.com');
        });
    }

    public function test_send_email_with_context_from_email_failure()
    {
        $user = User::factory()->create();

        $this->storeSendAttachedEmailAction([
            'from' => [
                'context' => [
                    'email' => 'responsibles.*.email',
                ],
            ],
            'recipients' => ['to' => ['context' => ['mailables' => ['user']]]],
        ]);

        Mail::fake();

        $this->expectExceptionMessage("several 'from' defined");
        SendAttachedEmail::dispatch(new SystemFile($this->getAssetPath()), $user);
    }
}
