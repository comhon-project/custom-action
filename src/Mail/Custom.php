<?php

namespace Comhon\CustomAction\Mail;

use Comhon\TemplateRenderer\Facades\Template;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class Custom extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @param  array  $mail  mail informations like subject, body...
     * @param  array  $replacements  list of values that may be replaced in mail content
     * @param  string  $defaultLocale  the default locale that should be used
     * @param  string  $defaultTimezone  the default timezone that should be used
     * @param  string  $preferredTimezone  the timezone to use when needed based on reader preferences.
     */
    public function __construct(
        private array $mail,
        private array $replacements = [],
        private ?string $defaultLocale = null,
        private ?string $defaultTimezone = null,
        private ?string $preferredTimezone = null
    ) {
        foreach (['subject', 'body'] as $property) {
            if (! isset($this->mail[$property])) {
                throw new \Exception("missing required mail $property");
            }
        }
    }

    /**
     * Render template.
     *
     * @return string
     */
    private function renderTemplate(string $template)
    {
        return Template::render(
            $template,
            $this->replacements,
            $this->defaultLocale,
            $this->defaultTimezone,
            $this->preferredTimezone
        );
    }

    /**
     * Get the message envelope.
     *
     * @return \Illuminate\Mail\Mailables\Envelope
     */
    public function envelope()
    {
        return new Envelope(subject: $this->renderTemplate($this->mail['subject']));
    }

    /**
     * Get the message content definition.
     *
     * @return \Illuminate\Mail\Mailables\Content
     */
    public function content()
    {
        return new Content(htmlString: $this->renderTemplate($this->mail['body']));
    }

    /**
     * Get the attachments for the message.
     *
     * @return array
     */
    public function attachments()
    {
        $attachments = [];
        if (isset($this->mail['attachments'])) {
            foreach ($this->mail['attachments'] as $path) {
                $attachments[] = Attachment::fromPath($path);
            }
        }

        return $attachments;
    }
}
