<?php

namespace Tests\Unit;

use App\Models\User;
use Comhon\CustomAction\Mail\Custom;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Mailables\Attachment;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\Utils;
use Tests\TestCase;

class CustomMailTest extends TestCase
{
    use RefreshDatabase;

    private function getAssetPath(): string
    {
        return Utils::joinPaths(Utils::getTestPath('Data'), 'jc.jpeg');
    }

    public function test_custom_mail()
    {
        $user = User::factory()->create();
        $mailable = new Custom([
            'subject' => 'Welcome {{ user.first_name }}',
            'body' => 'Welcome {{ user.first_name }} {{ user.name }}',
            'attachments' => [$this->getAssetPath()],
        ], ['user' => $user]);

        $mailable->assertHasSubject("Welcome {$user->first_name}");
        $mailable->assertSeeInHtml("Welcome {$user->first_name} {$user->name}");
        $mailable->assertHasAttachment(Attachment::fromPath($this->getAssetPath()));
    }

    #[DataProvider('providerCustomMailMissingRequiredValues')]
    public function test_custom_mail_missing_required_values($missingProperty)
    {
        $user = User::factory()->create();

        $mail = [
            'subject' => 'Welcome {{ user.first_name }}',
            'body' => 'Welcome {{ user.first_name }} {{ user.name }}',
            'attachments' => [$this->getAssetPath()],
        ];
        unset($mail[$missingProperty]);

        $this->expectExceptionMessage("missing required mail $missingProperty");
        new Custom($mail, ['user' => $user]);
    }

    public static function providerCustomMailMissingRequiredValues()
    {
        return [
            ['subject'],
            ['body'],
        ];
    }
}
