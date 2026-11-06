<?php

namespace Tests\Feature\SendEmail;

use App\Actions\SendRenderableEmail;
use App\Events\MyRenderableEmailEvent;
use App\Models\User;
use Comhon\CustomAction\Actions\Email\AbstractSendEmail;
use Comhon\CustomAction\Facades\CustomActionModelResolver;
use Comhon\CustomAction\Models\EventAction;
use Comhon\CustomAction\Models\EventListener;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Event;
use Tests\SetUpWithModelRegistrationTrait;
use Tests\TestCase;

class RenderableContextTest extends TestCase
{
    use RefreshDatabase;
    use SetUpWithModelRegistrationTrait {
        setUp as setUpWithModelRegistration;
    }

    protected function setUp(): void
    {
        $this->setUpWithModelRegistration();

        CustomActionModelResolver::bind('my-renderable-email-event', MyRenderableEmailEvent::class);
        CustomActionModelResolver::bind('send-renderable-email', SendRenderableEmail::class);
        MyRenderableEmailEvent::$renderableContextCalls = 0;

        // real queue and mailer: the queued mail is serialized, then rendered
        config(['queue.default' => 'sync', 'mail.default' => 'array']);
    }

    protected function tearDown(): void
    {
        AbstractSendEmail::registerDefaultTimeZone(null);

        parent::tearDown();
    }

    public function test_renderable_context_of_queued_email()
    {
        App::setLocale('en');
        AbstractSendEmail::registerDefaultTimeZone('Europe/Paris');
        $user = User::factory(['preferred_locale' => 'fr', 'preferred_timezone' => 'America/New_York'])->create();

        $eventListener = EventListener::factory()->event(MyRenderableEmailEvent::class)->create();
        EventAction::factory()
            ->action(SendRenderableEmail::class, $eventListener)
            ->withDefaultSettings(
                ['recipients' => ['to' => ['context' => ['mailables' => ['user']]]]],
                [
                    'fr' => [
                        'subject' => 'sujet {{ trace }} {{ hook_locale }}',
                        'body' => "{{ meeting_at|date('H:i') }} / {{ meeting_at|date('H:i', preferred_timezone) }}"
                            ." / {{ birthday|date('d/m', preferred_timezone) }}"
                            .' / {{ info.status.translate() }} / {{ info.code.translate() }}',
                    ],
                ],
            )
            ->create();

        $processedJobs = 0;
        Event::listen(JobProcessed::class, function () use (&$processedJobs) {
            $processedJobs++;
        });

        MyRenderableEmailEvent::dispatch($user, '2026-12-12 14:00', ['status' => 'ok', 'code' => 'abc']);

        $this->assertEquals(1, $processedJobs, 'the email should be queued');

        $messages = app('mailer')->getSymfonyTransport()->messages();
        $this->assertCount(1, $messages);
        $email = $messages[0]->getOriginalMessage();

        // hooks are chained (event, then action), with the locale of the render applied
        $this->assertEquals('sujet event-action fr', $email->getSubject());

        // meeting at 14:00 in Paris (08:00 in New York), birthday never shifted,
        // event translation, and action translation applied on a value exposed by the event
        $this->assertEquals('14:00 / 08:00 / 12/12 / status ok fr / abc-fr', $email->getHtmlBody());

        // resolved once for the subject and the body
        $this->assertEquals(1, MyRenderableEmailEvent::$renderableContextCalls);

        // the locale of the render is restored
        $this->assertEquals('en', App::getLocale());
    }
}
