<?php

namespace App\Events;

use App\Actions\ComplexEventAction;
use App\Exceptions\TestRenderableException;
use App\Models\User;
use Comhon\CustomAction\Contracts\CustomEventInterface;
use Comhon\CustomAction\Contracts\ExposeContextInterface;
use Comhon\CustomAction\Contracts\FakableInterface;
use Comhon\CustomAction\Contracts\HasFakeStateInterface;
use Comhon\CustomAction\Contracts\HasTranslatableContextInterface;
use Comhon\CustomAction\Services\ActionService;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MyComplexEvent implements CustomEventInterface, ExposeContextInterface, FakableInterface, HasFakeStateInterface, HasTranslatableContextInterface
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public User $user) {}

    public static function fake(?array $state = null): static
    {
        if (! ActionService::isFakingSafe()) {
            throw new \RuntimeException('Not in safe fake');
        }

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

    public static function getAllowedActions(): array
    {
        return [
            ComplexEventAction::class,
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

    public static function getTranslatableContext(): array
    {
        return [
            'user.translation' => fn ($value, $locale) => match ($locale) {
                'fr' => "statut francais : $value",
                'en' => "english status : $value",
            },
        ];
    }
}
