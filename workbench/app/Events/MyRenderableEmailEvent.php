<?php

namespace App\Events;

use App\Actions\SendRenderableEmail;
use App\Models\User;
use Comhon\CustomAction\Contracts\CustomEventInterface;
use Comhon\CustomAction\Contracts\ExposeContextInterface;
use Comhon\CustomAction\Contracts\HasRenderableContextInterface;
use Comhon\CustomAction\Contracts\HasTranslatableContextInterface;
use Comhon\CustomAction\Rules\RuleHelper;
use Comhon\TemplateRenderer\RenderContext;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MyRenderableEmailEvent implements CustomEventInterface, ExposeContextInterface, HasRenderableContextInterface, HasTranslatableContextInterface
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public static int $renderableContextCalls = 0;

    public function __construct(
        public User $user,
        public string $meeting_at,
        public array $info,
    ) {}

    public static function getAllowedActions(): array
    {
        return [
            SendRenderableEmail::class,
        ];
    }

    public static function getContextSchema(): array
    {
        return [
            'user' => RuleHelper::getRuleName('is').':mailable-entity',
            'user.name' => 'string',
            'user.email' => 'email',
            'meeting_at' => 'string',
            'info.status' => 'string',
            'info.code' => 'string',
        ];
    }

    public static function getRenderableContext(array $context, RenderContext $renderContext): array
    {
        static::$renderableContextCalls++;
        $context['meeting_at'] = $renderContext->dateTime($context['meeting_at']);
        $context['trace'] = 'event';

        return $context;
    }

    public static function getTranslatableContext(): array
    {
        return [
            'info.status' => fn ($value, $locale) => "status $value $locale",
        ];
    }
}
