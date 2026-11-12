<?php

namespace Tests\Unit\SendEmail;

use App\Models\User;
use Comhon\CustomAction\Actions\Email\Mailable\Custom;
use Comhon\CustomAction\Context\Translatable;
use Comhon\TemplateRenderer\RenderContext;
use Comhon\TemplateRenderer\RenderOptions;
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

    public function test_custom_mail_subject_is_not_html_escaped()
    {
        $mailable = new Custom([
            'subject' => 'Subject {{ name }}',
            'body' => 'Body {{ name }}',
        ], ['name' => "O'Brien & <Co>"]);

        $mailable->assertHasSubject("Subject O'Brien & <Co>");
        $mailable->assertSeeInHtml('Body O&#039;Brien &amp; &lt;Co&gt;', false);
    }

    public function test_custom_mail_with_replacements_closure()
    {
        $calls = 0;
        $mailable = new Custom([
            'subject' => "meeting {{ meeting|date('H:i') }}",
            'body' => "meeting {{ meeting|date('H:i', preferred_timezone) }} {{ locale }}",
        ], function (RenderContext $context) use (&$calls) {
            $calls++;

            return [
                'meeting' => $context->dateTime('2026-12-12 14:00'),
                'locale' => $context->locale(),
            ];
        }, new RenderOptions(locale: 'fr', timezone: 'Europe/Paris', preferredTimezone: 'Asia/Tokyo'));

        $mailable->assertHasSubject('meeting 14:00');
        $mailable->assertSeeInHtml('meeting 22:00 fr');

        // resolved once, then reused by the following renders
        $this->assertEquals(1, $calls);
    }

    public function test_custom_mail_replacements_closure_returning_generator()
    {
        $mailable = new Custom([
            'subject' => 'subject {{ name }}',
            'body' => 'body {{ name }}',
        ], static function () {
            yield 'name' => 'john';
        });

        // the generator is iterated once, then reused by the following renders
        $mailable->assertHasSubject('subject john');
        $mailable->assertSeeInHtml('body john');
    }

    public function test_custom_mail_serialization_after_render()
    {
        $mailable = new Custom([
            'subject' => 'subject {{ value.translate() }}',
            'body' => 'body {{ value.translate() }}',
        ], static fn () => [
            // the translator is a closure, the resolved replacements cannot be serialized
            'value' => new Translatable('foo', fn ($value, $locale) => "$value-$locale"),
        ], new RenderOptions(locale: 'fr'));

        $mailable->assertHasSubject('subject foo-fr');

        /** @var Custom $restored */
        $restored = unserialize(serialize($mailable));

        $restored->assertHasSubject('subject foo-fr');
        $restored->assertSeeInHtml('body foo-fr');
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
