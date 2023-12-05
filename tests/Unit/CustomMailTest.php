<?php

namespace Tests\Unit;

use Comhon\CustomAction\Mail\Custom;
use Comhon\CustomAction\Tests\Support\Models\User;
use Comhon\CustomAction\Tests\TestCase;
use Illuminate\Mail\Mailables\Attachment;

class CustomMailTest extends TestCase
{
    private static $asset = __DIR__
        .DIRECTORY_SEPARATOR.'..'
        .DIRECTORY_SEPARATOR.'Data'
        .DIRECTORY_SEPARATOR.'jc.jpeg';

    public function testCustomMail()
    {
        $user = User::factory()->create();
        $mailable = new Custom([
            'subject' => 'Welcome {{ user.first_name }}',
            'body' => 'Welcome {{ user.first_name }} {{ user.name }}',
            'attachments' => [self::$asset],
        ], ['user' => $user]);

        $mailable->assertHasSubject("Welcome {$user->first_name}");
        $mailable->assertSeeInHtml("Welcome {$user->first_name} {$user->name}");
        $mailable->assertHasAttachment(Attachment::fromPath(self::$asset));
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
            'attachments' => [self::$asset],
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
