<?php

namespace Comhon\CustomAction\Mail;

use Comhon\CustomAction\Exceptions\CustomMailableException;
use Comhon\TemplateRenderer\Facades\Template;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Mail\Attachable;
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
     * @param  array  $mail  mail informations (handled keys: subject, body, attachments, from)
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
                throw new CustomMailableException("missing required mail $property");
            }
        }
    }

    /**
     * Render template.
     */
    private function renderTemplate(string $template): string
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
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: $this->mail['from'] ?? null,
            subject: $this->renderTemplate($this->mail['subject'])
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(htmlString: $this->renderTemplate($this->mail['body']));
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        $attachments = [];
        if (isset($this->mail['attachments'])) {
            foreach ($this->mail['attachments'] as $file) {
                $attachments[] = $file instanceof Attachable
                    ? $file
                    : Attachment::fromPath($file);
            }
        }

        return $attachments;
    }
}
