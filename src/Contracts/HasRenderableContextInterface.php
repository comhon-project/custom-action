<?php

namespace Comhon\CustomAction\Contracts;

use Comhon\TemplateRenderer\RenderContext;

interface HasRenderableContextInterface
{
    /**
     * Get the context to render in email templates.
     *
     * Called at render time, once the locale and timezones are resolved (the locale is applied).
     * Receives the validated context, translations are applied afterwards on the returned context.
     * May be called once per email, keep it free of side effects.
     */
    public static function getRenderableContext(array $context, RenderContext $renderContext): array;
}
