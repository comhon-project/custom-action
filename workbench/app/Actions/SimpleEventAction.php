<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Output;
use Comhon\CustomAction\Actions\CallableFromEventTrait;
use Comhon\CustomAction\Actions\InteractWithContextTrait;
use Comhon\CustomAction\Actions\InteractWithSettingsTrait;
use Comhon\CustomAction\Contracts\CallableFromEventInterface;
use Comhon\CustomAction\Contracts\CustomActionInterface;
use Comhon\CustomAction\Resolver\CustomActionModelResolver;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SimpleEventAction implements CallableFromEventInterface, CustomActionInterface
{
    use CallableFromEventTrait,
        Dispatchable,
        InteractsWithQueue,
        InteractWithContextTrait,
        InteractWithSettingsTrait,
        Queueable,
        SerializesModels;

    public static function getSettingsSchema(?string $eventClassContext = null): array
    {
        return [
            'text' => ['required', 'string'],
        ];
    }

    public static function getLocalizedSettingsSchema(?string $eventClassContext = null): array
    {
        return [
            'localized_text' => ['required', 'string'],
        ];
    }

    public function handle(CustomActionModelResolver $resolver)
    {
        $setting = $this->getSetting();
        $localizedSetting = $this->getLocalizedSetting();
        $context = $this->getExposedContext(true, true);

        Output::create([
            'action' => $resolver->getUniqueName(static::class),
            'setting_id' => $setting->id,
            'setting_class' => get_class($setting),
            'localized_setting_id' => $localizedSetting?->id,
            'output' => [
                'text' => $setting->settings['text'],
                'localized_text' => $localizedSetting->settings['localized_text'] ?? null,
                'context' => $context,
            ],
        ]);
    }
}
