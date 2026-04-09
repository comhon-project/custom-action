# Actions Triggered From Events

## Custom Event

Actions can be triggered when [events](https://laravel.com/docs/master/events) are dispatched. To define an event that can trigger custom actions, the event must implement interface `CustomEventInterface`. You will  have to implements the function `getAllowedActions` that must return actions that can be triggered from the event.

```php
namespace App\Events;

use App\Models\User;
use App\Actions\CustomActions\NotifyAdmin;
use Comhon\CustomAction\Contracts\CustomEventInterface;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserRegistered implements CustomEventInterface
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public User $user) {}

    public static function getAllowedActions(): array
    {
        return [
            NotifyAdmin::class,
        ];
    }
}
```

## Event Action

To define an action that can be triggered by an event, the action must implement interface `CustomActionInterface` and `CallableFromEventInterface`. The action should use the trait `CallableFromEventTrait`. As Manual actions, you will have to implements `getSettingsSchema` and `getLocalizedSettingsSchema`.

### Generation Action

To quickly generate event action, you should use the `custom-action:generate` Artisan commands:

```bash
php artisan custom-action:generate NotifyAdmin --callable=from-event
```

It will generate the following class : 

```php
namespace App\Actions\CustomActions;

use Comhon\CustomAction\Actions\CallableFromEventTrait;
use Comhon\CustomAction\Actions\InteractWithContextTrait;
use Comhon\CustomAction\Actions\InteractWithSettingsTrait;
use Comhon\CustomAction\Contracts\CallableFromEventInterface;
use Comhon\CustomAction\Contracts\CustomActionInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class NotifyAdmin implements CustomActionInterface, CallableFromEventInterface
{
    use Dispatchable,
        Queueable,
        InteractsWithQueue,
        SerializesModels,
        InteractWithContextTrait,
        InteractWithSettingsTrait,
        CallableFromEventTrait;

    public static function getSettingsSchema(?string $eventClassContext = null): array
    {
        return [];
    }

    public static function getLocalizedSettingsSchema(?string $eventClassContext = null): array
    {
        return [];
    }

    public function handle()
    {
        return;
    }
}
```

The generated class provides helpful traits to interact with [context](Context.md) and [settings](Settings.md).

## How to Trigger Event Actions

### Event Listeners

To actually configure actions to be triggered from events, you will have to configure events listeners. Normally, events listeners are configurable through the Customization API. However, for explanation purposes, we will create one using Eloquent Model.

```php
use Comhon\CustomAction\Models\EventListener;

$eventListener = new EventListener([
    'event' => UserRegistered::class
]);
$eventListener->save();
```

You can associate as many event listeners as you want to an event.

### Event Actions

Once the event listener is created you will have to configure an event action to be triggered from this event listener. Normally, event actions are configurable through the Customization API. However, for explanation purposes, we will create one using Eloquent Model.


```php
use Comhon\CustomAction\Models\EventAction;

$action = new EventAction();
$action->type = 'notify-admin';
$action->eventListener()->associate($eventListener);
$action->save();
```

You can associate as many event actions as you want to an event listener. You can also associate the same event action multiple times — for example, if you have a very generic action like `send-automatic-email` that is fully customizable.

### Scoped Event Listeners

You can scope event listeners to trigger their associated actions only in a certain [context](Context.md). To do so, you just have to define the scope on the event listener.

```php
use Comhon\CustomAction\Models\EventListener;

$eventListener = new EventListener([
    'event' => UserRegistered::class,
    'scope' => [
        'user.is_vip' => true
    ]
]);
$eventListener->save();
```

The event listener will trigger its actions only if the `UserRegistered` event has a VIP user in its context.

When using default [ContextScoper](Library-Core#context-scoper), each key of your scope must be a path to a property (using dot notation).

```
is_vip ✅
company.office.address ✅
```

A possible use case could be to have one event listener that triggers actions for all users, and another event listener that triggers some additional actions only for VIP users.