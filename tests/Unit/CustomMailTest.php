<?php

namespace Tests\Unit;

use App\Models\User;
use Comhon\CustomAction\Mail\Custom;
use Illuminate\Mail\Mailables\Attachment;
use Tests\Support\Utils;
use Tests\TestCase;

class CustomMailTest extends TestCase
{
    private function getAssetPath(): string
    {
        return Utils::joinPaths(Utils::getTestPath('Data'), 'jc.jpeg');
    }

    public function testCustomMail()
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

    /**
     * @dataProvider providerCustomMailMissingRequiredValues
     */
    public function testCustomMailMissingRequiredValues($missingProperty)
    {
        $user = User::factory()->create();

        $mail = [
            'subject' => 'Welcome {{ user.first_name }}',
            'body' => 'Welcome {{ user.first_name }} {{ user.name }}',
            'attachments' => [$this->getAssetPath()],
        ];
        unset($mail[$missingProperty]);

        $this->expectExceptionMessage("missing required mail $missingProperty");
        $mailable = new Custom($mail, ['user' => $user]);
    }

    public static function providerCustomMailMissingRequiredValues()
    {
        return [
            ['subject'],
            ['body'],
        ];
    }
}
