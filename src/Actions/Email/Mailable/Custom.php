<?php

namespace Comhon\CustomAction\Actions\Email\Mailable;

use Comhon\CustomAction\Actions\Email\Exceptions\CustomMailableException;
use Comhon\TemplateRenderer\Facades\Template;
use Comhon\TemplateRenderer\RenderContext;
use Comhon\TemplateRenderer\RenderOptions;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Mail\Attachable;
use Illuminate\Mail\Attachment as BaseAttachment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Laravel\SerializableClosure\SerializableClosure;

class Custom extends Mailable
{
    use Queueable;
    use SerializesModels {
        __serialize as serializeModels;
    }

    /**
     * The replacements, a closure is wrapped to be serializable when the mail is queued.
     */
    private array|SerializableClosure $replacements;

    /**
     * Replacements returned by the closure at the first render, reused by the following ones
     * (subject and body are rendered separately, with the same render options).
     */
    private ?array $resolvedReplacements = null;

    private ?string $renderedSubject = null;

    private ?string $renderedBody = null;

    /**
     * Create a new message instance.
     *
     * @param  array  $mail  mail informations (handled keys: subject, body, attachments, from)
     * @param  array|\Closure(RenderContext): array  $replacements  values that may be replaced in mail content,
     *                                                              or a closure called at render time that returns them.
     *                                                              If the mail is queued, what the closure captures (`use`, arrow function, `$this`)
     *                                                              must be serializable (no closure, nor object holding one).
     * @param  RenderOptions|null  $renderOptions  the locale and timezones of the render
     */
    public function __construct(
        private array $mail,
        array|\Closure $replacements = [],
        private ?RenderOptions $renderOptions = null,
    ) {
        foreach (['subject', 'body'] as $property) {
            if (! isset($this->mail[$property])) {
                throw new CustomMailableException("missing required mail $property");
            }
        }
        $this->replacements = $replacements instanceof \Closure
            ? new SerializableClosure($replacements)
            : $replacements;
    }

    /**
     * Render template.
     */
    private function renderTemplate(string $template): string
    {
        return Template::render($template, $this->resolveReplacements(), $this->renderOptions);
    }

    /**
     * Get the replacements to render. The result of a closure is resolved at the first render, then reused.
     */
    private function resolveReplacements(): array|\Closure
    {
        if (! $this->replacements instanceof SerializableClosure) {
            return $this->replacements;
        }

        return function (RenderContext $context): array {
            if ($this->resolvedReplacements === null) {
                $resolved = $this->replacements->getClosure()($context);

                // a Traversable (e.g. a generator) can be iterated only once
                $this->resolvedReplacements = $resolved instanceof \Traversable
                    ? iterator_to_array($resolved)
                    : $resolved;
            }

            return $this->resolvedReplacements;
        };
    }

    /**
     * Edge case: if the mail is rendered before being queued (e.g. a preview, then queue()),
     * the resolved replacements are set and may not be serializable (e.g. objects holding closures).
     * So they are not serialized, and are resolved again at the next render.
     */
    public function __serialize(): array
    {
        $resolvedReplacements = $this->resolvedReplacements;
        $this->resolvedReplacements = null;

        try {
            return $this->serializeModels();
        } finally {
            $this->resolvedReplacements = $resolvedReplacements;
        }
    }

    /**
     * Render the subject and the body now instead of when the mail is sent,
     * so a rendering failure happens now. Rendered values are reused when the mail is sent.
     */
    public function prerender(): static
    {
        $this->getRenderedSubject();
        $this->getRenderedBody();

        return $this;
    }

    private function getRenderedSubject(): string
    {
        return $this->renderedSubject ??= $this->renderTemplate($this->mail['subject']);
    }

    private function getRenderedBody(): string
    {
        return $this->renderedBody ??= $this->renderTemplate($this->mail['body']);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: $this->mail['from'] ?? null,
            subject: $this->getRenderedSubject()
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(htmlString: $this->getRenderedBody());
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        $attachments = [];
        if (isset($this->mail['attachments'])) {
            foreach ($this->mail['attachments'] as $file) {
                $attachments[] = $file instanceof Attachable || $file instanceof BaseAttachment
                    ? $file
                    : Attachment::fromPath($file);
            }
        }

        return $attachments;
    }
}
