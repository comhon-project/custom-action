<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Output;
use Comhon\CustomAction\Actions\CallableFromEventTrait;
use Comhon\CustomAction\Actions\InteractWithContextTrait;
use Comhon\CustomAction\Actions\InteractWithSettingsTrait;
use Comhon\CustomAction\Context\ContextHelper;
use Comhon\CustomAction\Contracts\CallableFromEventInterface;
use Comhon\CustomAction\Contracts\CustomActionInterface;
use Comhon\CustomAction\Contracts\ExposeContextInterface;
use Comhon\CustomAction\Contracts\HasContextKeysIgnoredForScopedSettingInterface;
use Comhon\CustomAction\Contracts\SimulatableInterface;
use Comhon\CustomAction\Resolver\CustomActionModelResolver;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\App;

class ComplexEventAction implements CallableFromEventInterface, CustomActionInterface, ExposeContextInterface, HasContextKeysIgnoredForScopedSettingInterface, SimulatableInterface
{
    use CallableFromEventTrait,
        Dispatchable,
        InteractsWithQueue,
        InteractWithContextTrait,
        InteractWithSettingsTrait,
        Queueable,
        SerializesModels;

    public $actionEmail = 'action.email@gmail.com';

    public $ignoredEmail = 'ignored.email@gmail.com';

    public static function getSettingsSchema(?string $eventClassContext = null): array
    {
        $schema = [
            'text' => ['required', 'text_template'],
        ];
        $contextSchema = ContextHelper::mergeContextSchemas([$eventClassContext, static::class]);
        foreach (static::getContextKeysIgnoredForScopedSetting() as $contextKey) {
            unset($contextSchema[$contextKey]);
        }

        if (count($contextSchema)) {
            $typeBySettingKey = ['emails.*' => 'email'];
            $rules = ContextHelper::getContextKeyEnumRuleAccordingType($typeBySettingKey, $contextSchema, true);
            $schema = array_merge($schema, $rules);
        }

        return $schema;
    }

    public static function getLocalizedSettingsSchema(?string $eventClassContext = null): array
    {
        return [
            'localized_text' => ['required', 'html_template'],
        ];
    }

    public static function getContextSchema(): array
    {
        return [
            'actionEmail' => ['email'],
            'ignoredEmail' => ['email'],
        ];
    }

    public static function getContextKeysIgnoredForScopedSetting(): array
    {
        return ['ignoredEmail'];
    }

    public function handle(CustomActionModelResolver $resolver)
    {
        Output::create($this->process($resolver));
    }

    public function simulate(CustomActionModelResolver $resolver)
    {
        return $this->process($resolver);
    }

    public function process(CustomActionModelResolver $resolver)
    {
        $setting = $this->getSetting();
        $localizedSetting = $this->getLocalizedSetting($this->getEvent()->user);

        App::setLocale($localizedSetting->locale);
        $context = $this->getExposedContext(true, true);

        return [
            'action' => $resolver->getUniqueName(static::class),
            'setting_id' => $setting->id,
            'setting_class' => get_class($setting),
            'localized_setting_id' => $localizedSetting?->id,
            'output' => [
                'text' => $setting->settings['text'],
                'localized_text' => $localizedSetting->settings['localized_text'] ?? null,
                'user_id' => $context['user']['id'],
                'user_status' => $context['user']['status'],
                'user_translation' => $context['user']['translation']->translate(),
            ],
        ];
    }
}
