<?php

namespace Tests\Feature;

use App\Actions\SendManualUserRegisteredEmail;
use App\Models\User;
use Comhon\CustomAction\Mail\Custom;
use Comhon\CustomAction\Models\DefaultSetting;
use Comhon\CustomAction\Models\LocalizedSetting;
use Comhon\CustomAction\Models\ManualAction;
use Comhon\CustomAction\Services\ActionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Tests\SetUpWithModelRegistrationTrait;
use Tests\TestCase;

class SimulatationTest extends TestCase
{
    use RefreshDatabase;
    use SetUpWithModelRegistrationTrait;

    #[DataProvider('providerBoolean')]
    public function test_simulate_manual_email_success($grouped)
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

        $users = $grouped ? [$user, User::factory()->create()] : [$user];
        $preview = (new SendManualUserRegisteredEmail($users, $grouped))->simulate();

        Mail::assertSent(Custom::class, 0);

        $this->assertArrayHasKey('subject', $preview);
        $this->assertArrayHasKey('body', $preview);

        $expected = "Dear {$user->first_name}";
        $this->assertEquals($expected, $preview['subject']);

        $expected = "Dear {$user->first_name}, you have been registered !";
        $this->assertEquals($expected, $preview['body']);
    }

    public function test_simulate_manual_email_failure()
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
        (new SendManualUserRegisteredEmail($users))->simulate();
    }

    public function test_simulate_function_doesnt_exists()
    {
        $action = ManualAction::factory(['type' => 'bad-action'])->create();

        $this->expectExceptionMessage("simulate method doesn't exist on class App\Actions\BadAction");
        app(ActionService::class)->simulate($action, []);
    }

    public function test_doesnt_have_fake_state()
    {
        $action = ManualAction::factory(['type' => 'bad-action'])->create();

        $this->expectException(UnprocessableEntityHttpException::class);
        $this->expectExceptionMessage('bad-action has no state to simulate action');
        app(ActionService::class)->simulate($action, ['states' => ['foo']]);
    }
}
