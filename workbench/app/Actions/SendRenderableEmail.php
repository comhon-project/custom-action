<?php

namespace App\Actions;

use Comhon\CustomAction\Actions\Email\SendAutomaticEmail;
use Comhon\CustomAction\Contracts\HasRenderableContextInterface;
use Comhon\CustomAction\Contracts\HasTranslatableContextInterface;
use Comhon\TemplateRenderer\RenderContext;

class SendRenderableEmail extends SendAutomaticEmail implements HasRenderableContextInterface, HasTranslatableContextInterface
{
    protected $sendAsynchronously = true;

    public static function getRenderableContext(array $context, RenderContext $renderContext): array
    {
        $context['birthday'] = $renderContext->date('2026-12-12');
        $context['trace'] .= '-action';
        $context['hook_locale'] = app()->getLocale();

        return $context;
    }

    public static function getTranslatableContext(): array
    {
        return [
            // 'info' is exposed by the event: translations apply on the merged context
            'info.code' => fn ($value, $locale) => "$value-$locale",
        ];
    }
}
