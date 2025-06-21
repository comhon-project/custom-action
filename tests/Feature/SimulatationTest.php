<?php

namespace Tests\Feature;

use App\Actions\SendManualUserRegisteredEmail;
use App\Models\User;
use Comhon\CustomAction\Mail\Custom;
use Comhon\CustomAction\Models\DefaultSetting;
use Comhon\CustomAction\Models\LocalizedSetting;
use Comhon\CustomAction\Models\ManualAction;
use Comhon\CustomAction\Services\ActionService;
use Comhon\CustomAction\Support\EmailHelper;
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
                        'from' => ['static' => ['email' => 'from@gmail.com']],
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
        $simulations = (new SendManualUserRegisteredEmail($users, $grouped))->simulate();

        Mail::assertSent(Custom::class, 0);

        $this->assertArrayHasKey(0, $simulations);
        $this->assertCount(1, $simulations);
        $simulation = $simulations[0];

        $this->assertArrayHasKey('to', $simulation);
        $this->assertArrayHasKey('cc', $simulation);
        $this->assertArrayHasKey('bcc', $simulation);
        $this->assertArrayHasKey('from', $simulation);
        $this->assertArrayHasKey('subject', $simulation);
        $this->assertArrayHasKey('body', $simulation);

        $expected = json_decode(collect($users)->map(fn ($user) => EmailHelper::normalizeAddress($user))->toJson(), true);
        if (! $grouped) {
            $expected = $expected[0];
        }
        $this->assertEquals(
            $expected,
            json_decode(collect($simulation['to'])->toJson(), true)
        );

        $this->assertEquals('from@gmail.com', $simulation['from']?->address);

        $expected = "Dear {$user->first_name}";
        $this->assertEquals($expected, $simulation['subject']);

        $expected = "Dear {$user->first_name}, you have been registered !";
        $this->assertEquals($expected, $simulation['body']);
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
