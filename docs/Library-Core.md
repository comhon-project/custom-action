# Library Core

## Model Resolver

Callable action classes and event classes must be interfaced with the database and the Customization API, so we need to perform a mapping to avoid disclosing the PHP class to Customization API consumers. The Model Resolver is made for that, it associate a **unique name** to a **class name** and allow to retrieve one from the other.

You should define the map within your `AppServiceProvider` in the `boot` function.

```php
use Comhon\ModelResolverContract\ModelResolverInterface;

app(ModelResolverInterface::class)->register([
    'send-welcome-mail' => SendWelcomeMail::class,
    'user-registered' => UserRegistered::class,
]);
```

Custom Action library comes with a Model Resolver but you can use your own Model Resolver. To use your own Model Resolver, you must register it as singleton within your `AppServiceProvider` in the `register` function. Your Model Resolver must implements `ModelResolverInterface` interface.

```php
use Comhon\ModelResolverContract\ModelResolverInterface;

$this->app->singleton(ModelResolverInterface::class, MyModelResolver::class);
```

## Catalogs

### Manual Actions Catalog

Manual Actions Catalog is a simple list of manual actions that may be configured through Customization API.

To configure Manual Actions Catalog you should define a singleton within your `AppServiceProvider` in the `register` function.

```php
$this->app->singleton(ManualActionTypeCatalog::class, function ($app) {
    return new ManualActionTypeCatalog([
        'send-welcome-mail',
        // ...
    ]);
});
```

### Events Catalog

Events Catalog is a simple list of events that may be used through Customization API to configure event actions.

To configure Events Catalog you should define a singleton within your `AppServiceProvider` in the `register` function.

```php
$this->app->singleton(EventCatalog::class, function ($app) {
    return new EventCatalog([
        'user-registered',
        // ...
    ]);
});
```

## Context Scoper

This library allows to trigger event actions and select settings by using scope. The right event actions or the right settings are chosen using the provided scoper `Comhon\CustomAction\Context\ContextScoper`. But if you want to build more complex scopes you can provide your own scoper by defining a singleton in your `AppServiceProvider`. Your scoper must implements `ContextScoperInterface` interface.

```php
$this->app->singleton(ContextScoperInterface::class, MyContextScoper::class);
```