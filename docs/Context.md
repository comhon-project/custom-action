# Context

## What is the Context

Context is most commonly defined by the public class attributes of an instance of an action or an event.

```php
use Comhon\CustomAction\Contracts\CustomActionInterface;

class SendWelcomeEmail implements CustomActionInterface
{
    public $hardcoded = 'my hardcoded value'

    public function __construct(public User $user, public string $url, private string $company) {}
}
```
In the previous example, `$hardcoded`, `$user`, `$url` are part of the context. `$company` is not part of the context because it is private.

## Exposing the Context

If you want to expose the context to the Customization API, an action or an event must implements `ExposeContextInterface`.

You will have to implement the static function `getContextSchema` that exposes what your context is made of (The returned array MUST follow Laravel's [validation](https://laravel.com/docs/master/validation) logic).

### Action context

For example, if your application have a manual action called `SendWelcomeEmail` that sends an email to a newly registered user, you will likely inject the user into your action to define the email recipient. So, the user might be part of the exposed context.

```php
use Comhon\CustomAction\Contracts\CustomActionInterface;
use Comhon\CustomAction\Contracts\ExposeContextInterface;

class SendWelcomeEmail implements CustomActionInterface, ExposeContextInterface
{
    public function __construct(public User $user, public string $url) {}

    public static function getContextSchema(): array
    {
        return [
            'user.name' => 'required|string',
            'user.email' => 'required|email',
            'url' => 'url',
        ];
    }
}
```

### Event context

For example, if your application have an event called `RegisteredUser`, you will likely inject the user into this event. So, the user might be part of the context.

```php
use Comhon\CustomAction\Contracts\CustomEventInterface;
use Comhon\CustomAction\Contracts\ExposeContextInterface;

class RegisteredUser implements CustomEventInterface, ExposeContextInterface
{
    public function __construct(public User $user, public string $url) {}

    public static function getContextSchema(): array
    {
        return [
            'user.name' => 'required|string',
            'user.email' => 'required|email',
            'url' => 'url',
        ];
    }
}
```

When an action is triggered from an event, the event context is accessible within the action as it own context.

## Purposes of Context

### Determine which scoped settings to choose

As we have seen [here](Settings#scoped-settings), you can defined scoped settings that are selected according to the context.

For example, inside an action, if you define a context value like
```php
    public static function getContextSchema(): array
    {
        return [
            // ...
            'user.is_vip' => 'required|boolean',
            // ...
        ];
    }
```

You may define a scoped setting with the following scope : 
```json
{
    "user.is_vip": true
}
```

Within the action, if you call `$this->getSetting()` and if the user is a VIP for the current action instance, the scoped settings with `"user.is_vip": true` will be selected.

### Determine which event listener to execute (for event actions)

You can configure [event listeners](Actions-Triggered-From-Events#how-to-trigger-event-actions) to listen only certain events according to the context.

For example, inside an event, if you define a context value like
```php
    public static function getContextSchema(): array
    {
        return [
            // ...
            'user.is_vip' => 'required|boolean',
            // ...
        ];
    }
```

You may define an event listener with the following scope : 
```json
{
    "user.is_vip": true
}
```
An event listener with the previous scope will be executed only if the event instance has a VIP user.

### Interact with Context

During an action handling, you may need to interact with context. To retrieve all context values that can be exposed, you should use the `getExposedContext` method. The returned array will contain context defined in the action and potentially the context defined in the event, if the action is triggered by an event.

When You call this method:  
* Translations may be automatically injected into the returned array (see the Translatable Context chapter).  
* Context may be validated to prevent the use of invalid data or leakage of sensitive information (note that if the context is made of objects, typically eloquent models, values that are not part of validation still present after validation).  

```php
// in your action
$withTranslations = true;
$validated = true;
$context = $this->getExposedContext($withTranslations, $validated);
```

#### Example

For Example your application have an action that send an email, and in the settings of this action, you can customize the email content with an HTML template. It would be nice to have variable substitution. Something like : 

```php
<h1>Welcome {{ user.name }}</h1>
<p>You have been registered on our platform. Please click the link below.</p>
<a href="{{ url }}" target="_blank">Click here</a>
```

The method `getContextSchema` will expose, through the Customization API, which values can be used and what they are. This makes it easy to define HTML template with variables as setting. And During the action handling you will be able to easily replace variables in your template using context values retrieved with the `getExposedContext` method.

## Translatable Context

You can define context as translatable. To do so, your action or event must implement the `HasTranslatableContextInterface`. You also need to implement the `getTranslatableContext` method, which should return a list of all context values that should be translatable.  

Each key must be a context value path (dot notation) and each value can be a string or a callback.

```php

    public static function getTranslatableContext(): array
    {
        return [
            'user.civility' => '',
            'user.preferred_week_day' => 'days.',
            'user.nationalities.*.name' => 'nationalities.',
            'user.validated' => fn ($value, $locale) => match ($value) {
                true => $locale == 'fr' ? 'oui' : 'yes',
                false => $locale == 'fr' ? 'non' : 'no',
            },
        ];
    }
```

If a string is defined as value, it will be used as prefix to find the translation using Laravel [localization](https://laravel.com/docs/master/localization).

To have a better insight, let's see some examples : 
* `'user.civility' => ''`
  * if the corresponding context value is `mr` the translation will be found using `__('mr')`
  * if the corresponding context value is `ms` the translation will be found using `__('ms')`
* `'user.preferred_week_day' => 'days.'`
  * if the corresponding context value is `1` the translation will be found using `__('days.1')`
  * if the corresponding context value is `7` the translation will be found using `__('days.7')`
* `'user.nationalities.*.name' => 'nationalities.'`
  * the previous method to find translation will be used automatically for each nationalities
* `'user.validated' will use the given callback to translate the value

To access the context translation, you will have to call the function `translate()` on a translated context value obtained via `getExposedContext`.

```php
// in your action
$context = $this->getExposedContext(true);

// raw value
$raw = $context['user']['civility']->value;

App::setLocale('en');
$translated = $context['user']['civility']->translate();

// with the same instance, you can get the translation from another locale
App::setLocale('fr');
$translated = $context['user']['civility']->translate();

$first = $context['user']['nationalities'][0]['name']->translate() ?? null;
$second = $context['user']['nationalities'][1]['name']->translate() ?? null;
```

## Formating Context

For any reason you may want to format the context to expose values differently than the public attributes of the instantiated object. To do, you just have to implement the interface `FormatContextInterface`. You will have to implement the function `formatContext` that returns the transformed context. 

```php
use Comhon\CustomAction\Contracts\CustomActionInterface;
use Comhon\CustomAction\Contracts\ExposeContextInterface;
use Comhon\CustomAction\Contracts\FormatContextInterface;

class SendWelcomeEmail implements CustomActionInterface, ExposeContextInterface, FormatContextInterface
{
    public function __construct(private User $user, private Company $company, private string $redirectUrl) {}
    
    public static function getContextSchema(): array
    {
        // the schema should match the context returned in formatContext()
        return [
            'user_id' => 'required|int',
            'user_name' => 'required|string',
            'redirect.url' => 'required|string',
            'company.id' => 'required|int',
            'company.name' => 'required|string',
            'company.address' => 'required|string',
            'my_action_value' => 'required|string',
        ];
    }

    public function formatContext(): array
    {
        return [
            'user_id' => $this->user->id,
            'user_name' => $this->user->name,
            'redirect' => [
                'url' => $this->redirectUrl,
            ],
            'company' => $this->company,
            'my_action_value' => 'my harcoded value',
        ];
    }
}
```