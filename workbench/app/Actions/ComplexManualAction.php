<?php

declare(strict_types=1);

namespace App\Actions;

use App\Exceptions\TestRenderableException;
use App\Models\User;
use Comhon\CustomAction\Actions\CallableManuallyTrait;
use Comhon\CustomAction\Actions\InteractWithContextTrait;
use Comhon\CustomAction\Actions\InteractWithSettingsTrait;
use Comhon\CustomAction\Contracts\CustomActionInterface;
use Comhon\CustomAction\Contracts\ExposeContextInterface;
use Comhon\CustomAction\Contracts\FakableInterface;
use Comhon\CustomAction\Contracts\HasFakeStateInterface;
use Comhon\CustomAction\Contracts\HasTranslatableContextInterface;
use Comhon\CustomAction\Contracts\SimulatableInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ComplexManualAction implements CustomActionInterface, ExposeContextInterface, FakableInterface, HasFakeStateInterface, HasTranslatableContextInterface, SimulatableInterface
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
        $companyState = [];
        if (! empty($state)) {
            $companyState['status'] = '';
            foreach ($state as $value) {
                if (is_array($value) && ($value['status'] ?? null) == 1000) {
                    throw new TestRenderableException('message');
                }
                $companyState['status'] .= '-'.(is_array($value)
                    ? collect($value)->map(fn ($value, $key) => "{$key}_{$value}")->implode('')
                    : $value);
            }
        }

        return new static(User::factory($companyState)->create());
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
            'user.email' => 'email',
        ];
    }

    public static function getTranslatableContext(): array
    {
        return [
            'user.status' => '',
        ];
    }

    public function handle() {}

    public function simulate() {}
}
