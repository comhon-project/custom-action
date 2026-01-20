<?php

declare(strict_types=1);

namespace App\Actions;

use App\Exceptions\TestRenderableException;
use App\Models\Output;
use App\Models\User;
use Comhon\CustomAction\Actions\CallableManuallyTrait;
use Comhon\CustomAction\Actions\InteractWithContextTrait;
use Comhon\CustomAction\Actions\InteractWithSettingsTrait;
use Comhon\CustomAction\Contracts\CustomActionInterface;
use Comhon\CustomAction\Contracts\ExposeContextInterface;
use Comhon\CustomAction\Contracts\FakableInterface;
use Comhon\CustomAction\Contracts\FormatContextInterface;
use Comhon\CustomAction\Contracts\HasFakeStateInterface;
use Comhon\CustomAction\Contracts\HasTranslatableContextInterface;
use Comhon\CustomAction\Contracts\SimulatableInterface;
use Comhon\CustomAction\Resolver\CustomActionModelResolver;
use Comhon\CustomAction\Services\ActionService;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\App;

class ComplexManualAction implements CustomActionInterface, ExposeContextInterface, FakableInterface, FormatContextInterface, HasFakeStateInterface, HasTranslatableContextInterface, SimulatableInterface
{
    use CallableManuallyTrait,
        Dispatchable,
        InteractsWithQueue,
        InteractWithContextTrait,
        InteractWithSettingsTrait,
        Queueable,
        SerializesModels;

    public function __construct(public User $user) {}

    public static function fake(?array $state = null): static
    {
        ActionService::ensureFakingSafe();

        $userState = [];
        if (! empty($state)) {
            $userState['status'] = '';
            foreach ($state as $value) {
                if (is_array($value) && ($value['status'] ?? null) == 1000) {
                    throw new TestRenderableException('message');
                }
                $userState['status'] .= '-'.(is_array($value)
                    ? collect($value)->map(fn ($value, $key) => "{$key}_{$value}")->implode('')
                    : $value);
            }
        }

        return new static(User::factory($userState)->create());
    }

    public static function getFakeStateSchema(): array
    {
        return [
            'status_1',
            'status_2',
            'status_3',
            'status' => 'integer|min:10',
        ];
    }

    public static function getSettingsSchema(?string $eventClassContext = null): array
    {
        return [
            'text' => ['required', 'text_template'],
        ];
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
            'user.id' => 'integer',
            'user.status' => 'string',
            'user.translation' => 'string',
            'user.email' => 'email',
        ];
    }

    public function formatContext(): array
    {
        return [
            'user' => $this->user->toArray(),
        ];
    }

    public static function getTranslatableContext(): array
    {
        return [
            'user.translation' => fn ($value, $locale) => match ($locale) {
                'fr' => "statut francais : $value",
                'en' => "english status : $value",
            },
        ];
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
        $localizedSetting = $this->getLocalizedSetting($this->user);

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
