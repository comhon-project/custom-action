# Getting Started

## Custom Action

A custom action is the main concept to understand. A custom action is made up of two parts:
* A callable action class that will handle the action (ex: send an email).
* An Eloquent Model action stored in the database with a `type` attribute.

Both are associated by mapping the callable class name and the `type` value of the Eloquent model (for more information, see [Model Resolver](Library-Core#model-resolver)).

The Eloquent Model action has associated settings stored in the database. These settings will be usable inside the callable action class.

There are two kinds of custom actions: **Manual Actions** and **Actions Triggered From Events**.

### Manual Actions

A manual action is an action that will be invoked directly in the code (in a service or a controller, etc...).

```php
// something like
(new SendInvoiceEmail($invoice))->handle();

// or if your action is dispatchable
SendInvoiceEmail::dispatch($invoice);
```

### Actions Triggered From Events

An action that can be triggered from an [event](https://laravel.com/docs/master/events), also called an **event action**, is triggered when a particular event is dispatched. You can configure any action to be triggered on any event as long as it has been allowed.

In this chapter we will only explain manual actions, because they are easier to use. We recommend getting comfortable with manual actions before learning more about event actions.

## Creating Action Class

You can generate an action class using the `custom-action:generate` Artisan command. The generated action will be placed in the `app/Actions/CustomActions` directory. If this directory does not exist in your application, Laravel will create it for you:

```bash
php artisan custom-action:generate SendMyFirstEmail
```
Use the help option to see all available options
```bash
php artisan custom-action:generate -h
```
The generated action implements `CustomActionInterface` and uses some essential traits that will permit you to easily interact with your action settings and context.

## Settings
Settings allow actions to be customized and will be editable for users who have access to the [Customization API](Customization-API.md). (Nothing to do—the API is already ready to use!)  

The only thing you need to do is define your settings schema:
* This allows any client library that consumes the [Customization API](Customization-API.md) to build generic forms to store settings.
* This allows validating inputs from the [Customization API](Customization-API.md) before storing them.

## Customize your Action
Let's start with an example to have a better insight. Let's generate an action : 
```bash
php artisan custom-action:generate SendMyFirstEmail --callable=manually
```
This will create the following class
```php
<?php

declare(strict_types=1);

namespace App\Actions\CustomActions;

use Comhon\CustomAction\Actions\CallableManually;
use Comhon\CustomAction\Actions\InteractWithContextTrait;
use Comhon\CustomAction\Actions\InteractWithSettingsTrait;
use Comhon\CustomAction\Contracts\CustomActionInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendMyFirstEmail implements CustomActionInterface
{
    use Dispatchable,
        Queueable,
        InteractsWithQueue,
        SerializesModels,
        InteractWithContextTrait,
        InteractWithSettingsTrait,
        CallableManually;

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
Now let's implement a very simple email sending.

```php
    public function __construct(private User $user) {}

    public function handle()
    {
        \Illuminate\Support\Facades\Mail::send([], [], function ($message) {
            $message->to($this->user->email)
                    ->subject('My first email')
                    ->html('<h1>Hello!</h1><p>This is my first email</p>');
        });
    }
```
As you can see, the email definition is completely hardcoded. So now let's do the customization!

You may want the email sender to be customizable. To do so, Just add needed settings in the `getSettingsSchema` function.
```php
    public static function getSettingsSchema(?string $eventClassContext = null): array
    {
        return [
            'from' => 'required|email',
        ];
    }
```

Now you can use your setting to set the sender :
```php
    public function handle()
    {
        // retrieve settings from database
        $setting = $this->getSetting();

        \Illuminate\Support\Facades\Mail::send([], [], function ($message) {
            $message->to($this->user->email)
                ->from($setting['from'])
                ->subject('My first email')
                ->html('<h1>Hello!</h1><p>This is my first email</p>');
        });
    }
```

You may want the email subject and body to be customizable, and you want it to be localized. To do so, Just add needed settings in the `getLocalizedSettingsSchema` function.
```php
    public static function getLocalizedSettingsSchema(?string $eventClassContext = null): array
    {
        return [
            'subject' => 'required|string',
            'body' => 'required|string',
        ];
    }
```

Now you can use your setting to set the subject and the email body :
```php
    public function handle()
    {
        // retrieve settings from database
        $setting = $this->getSetting();

        // retrieve localized settings from database (throw exception if not found)
        $localizedSetting = $this->getLocalizedSettingOrFail($this->user->preferredLocale);

        \Illuminate\Support\Facades\Mail::send([], [], function ($message) {
            $message->to($this->user->email)
                ->from($setting['from'])
                ->subject($localizedSetting['subject'])
                ->html($localizedSetting['body']);
        });
    }
```

That's it! Your action is customized! Pretty easy, right?

But wait! Actually, there are two more steps to complete before making your action callable successfully:  
* You must register your action with the model resolver.
  * See [Model Resolver](Library-Core#model-resolver) to learn how to register an action and why we need this.
* The settings values must be stored in the database.
  * See [Settings](Settings.md) to learn more about what settings are and how they work.  
  * See [Customization API](Customization-API.md) to learn how to configure settings and more through the API.  
