# Settings

We saw in the *Getting Started* chapter how to add settings schema to your action. Now, let's go deeper to gain a better insight into what settings are and how they work.

Settings are stored in the database and are associated either with a manual action or an action triggered by an event (also called an **event action**). Each action can have default settings and **scoped** settings. In this chapter we will only use manual actions but settings work exactly the same with event actions.

## Action

First, an action must be created in database. Normally manual actions are automatically created when you use the Customization API to configure the action. However, for explanation purposes, we will create one using Eloquent Model.

```php
use Comhon\CustomAction\Models\ManualAction;

$action = new ManualAction();
$action->type = 'my-first-email';
$action->save();
```

Then Settings can be associated to the action.

## Default Settings

Normally, default settings are configurable through the Customization API. However, for explanation purposes, we will create one using Eloquent Model.

```php
use Comhon\CustomAction\Models\DefaultSetting;

$defaultSetting = new DefaultSetting([
    'settings' => [
        'from' => 'no-reply@my-company.com',
    ],
]);
$defaultSetting->action()->associate($action);
$defaultSetting->save();
```

## Scoped Settings

Scoped settings are settings that you want to apply only in certain situations. For example, if your application sends emails from `no-reply@my-company.com` but you want a more personal sender to send emails to your VIP customers, scoped settings are made for that!

Normally, scoped settings are configurable through the Customization API. However, for explanation purposes, we will create one using Eloquent Model.

```php
use Comhon\CustomAction\Models\ScopedSetting;

$scopedSetting = new ScopedSetting([
    'name' => 'vip-scope',
    'scope' => [
        'is_vip' => true,
    ],
    'settings' => [
        'from' => 'jane.doe@my-company.com',
    ],
]);
$scopedSetting->action()->associate($action);
$scopedSetting->save();
```

When using default [ContextScoper](Library-Core#context-scoper), each key of your scope must be a path to a property (using dot notation).
```
is_vip ✅
company.office.address ✅
```

To be selected, all key-value pairs in the scope of a `ScopedSetting` must match the [Context](Context.md) when handling an action.  

You can associate as many `ScopedSetting` as you want to an action.

### Priority

A priority can be defined on a scoped setting.

```php
$scopedSetting = new ScopedSetting([
    'name' => 'vip-scope',
    'scope' => ['is_vip' => true],
    'settings' => ['from' => 'jane.doe@my-company.com'],
    'priority' => 10 // optional
]);
```

When handling an action, if several scoped settings match the context, the one with the highest priority will be chosen.

## Localized Settings

Localized settings are settings that are configured according a locale. They can be associated to a default setting or a scoped setting.

Normally, localized settings are configurable through the Customization API. However, for explanation purposes, we will create one using Eloquent Model.

```php
use Comhon\CustomAction\Models\LocalizedSetting;

$localizedSetting = new LocalizedSetting([
    'locale' => 'fr',
    'settings' => [
        'subject' => 'Mon premier email',
        'body' => '<p>Bonjour! voici mon premier email</p>',
    ],
]);
$localizedSetting->localizable()->associate($setting);
$localizedSetting->save();
```

you can associate as many localized settings as you want to a setting.

## Define settings on your callable action

In order to be able to configure settings through the Customization API and to use settings in your callable action, you must define settings schemas in your callable action.

```php
class SendMyFirstEmail implements CustomActionInterface
{
    use InteractWithSettingsTrait,
        CallableManually;

    public static function getSettingsSchema(?string $eventClassContext = null): array
    {
        return [
            'from' => 'required|email'
        ];
    }

    public static function getLocalizedSettingsSchema(?string $eventClassContext = null): array
    {
        return [
            'subject' => 'required|string',
            'body' => 'required|string',
        ];
    }
```

The returned array MUST follow Laravel's [validation](https://laravel.com/docs/master/validation) logic.

Note: If you want to share the Settings schema through the API, you should not use callback validations, as they cannot be transmitted through the JSON format.

Note: The `$eventClassContext` parameter is designed for actions that can be triggered from events. It allows you to define different settings depending on which event triggered the action.

## Retrieve settings when handling action

In your callable action, you don't have to worry about how to select default settings or scoped settings; the correct ones will be retrieved by simply calling `$this->getSetting()` and `$this->getLocalizedSetting()`.

```php
class SendMyFirstEmail implements CustomActionInterface
{
    use InteractWithSettingsTrait,
        CallableManually;

    public function handle()
    {
        $setting = $this->getSetting();

        // nullable
        $localizedSetting = $this->getLocalizedSetting('fr');

        // not nullable
        $localizedSetting = $this->getLocalizedSettingOrFail('fr');
        
        // ...
    }
}
```

Note: If you want to use scoped settings, your action class must implement `ExposeContextInterface`. Indeed, the values defined in the `scope` property are compared to the context defined in the action class to find the right scoped settings. For more details about context, please see [Context](Context.md).

## Scoped Settings conflicts

Sometimes a called action may have a context that match several scoped settings with the same priority. By default, an exception is thrown, but you can define your own "scoped settings resolver" to give your action the chance to choose the right scope and complete successfully. To do so, you will need to define a function called `resolveScopedSettings` that must return an instance of `Illuminate\Database\Eloquent\Model\Setting`. It will be called with an array of possible settings as the first parameter.

```php
use Illuminate\Database\Eloquent\Model\Setting;

class SendMyFirstEmail implements CustomActionInterface
{
    public function resolveScopedSettings($possibleSettings): Setting
    {
        return reset($possibleSettings); // return the first setting
    }
}
```

## Force settings

You can enforce the use of specific settings during action handling. More precisely, the `getSetting()` function will return the specified setting.

```php
$action = new SendMyFirstEmail($arg);
$action->forceSetting($forcedSetting);
$action->handle();
```