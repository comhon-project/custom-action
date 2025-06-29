<?php

namespace Tests\Feature\SendEmail;

use App\Actions\SendManualSimpleEmail;
use App\Models\User;
use Comhon\CustomAction\Actions\Email\Mailable\Custom;
use Comhon\CustomAction\Actions\Email\Support\EmailHelper;
use Comhon\CustomAction\Models\DefaultSetting;
use Comhon\CustomAction\Models\LocalizedSetting;
use Comhon\CustomAction\Models\ManualAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\SetUpWithModelRegistrationTrait;
use Tests\TestCase;

class SendEmailSimulationTest extends TestCase
{
    use RefreshDatabase;
    use SetUpWithModelRegistrationTrait;

    #[DataProvider('providerBoolean')]
    public function test_simulate_manual_email_success($grouped)
    {
        $user = User::factory()->create();

        ManualAction::factory(['type' => 'send-manual-simple-email'])
            ->has(
                DefaultSetting::factory([
                    'settings' => [
                        'recipients' => ['to' => ['context' => ['mailables' => ['to.*']]]],
                        'from' => ['static' => ['email' => 'from@gmail.com']],
                    ],
                ])->has(
                    LocalizedSetting::factory([
                        'locale' => 'en',
                        'settings' => [
                            'subject' => $grouped ? 'Dear group' : 'Dear {{ to.first_name }}',
                            'body' => $grouped
                                ? 'Dear group, you have been registered !'
                                : 'Dear {{ to.first_name }}, you have been registered !',
                        ],
                    ]),
                    'localizedSettings',
                ),
                'defaultSetting'
            )->create();

        Mail::fake();

        $users = $grouped ? [$user, User::factory()->create()] : [$user];
        $simulations = (new SendManualSimpleEmail($users, grouped: $grouped))->simulate();

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

        $expected = $grouped ? 'Dear group' : "Dear {$user->first_name}";
        $this->assertEquals($expected, $simulation['subject']);

        $expected = $grouped
            ? 'Dear group, you have been registered !'
            : "Dear {$user->first_name}, you have been registered !";
        $this->assertEquals($expected, $simulation['body']);
    }
}
