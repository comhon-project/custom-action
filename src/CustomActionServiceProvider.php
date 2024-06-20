<?php

namespace Comhon\CustomAction;

use Comhon\CustomAction\Commands\GenerateActionCommand;
use Comhon\CustomAction\Contracts\CustomActionInterface;
use Comhon\CustomAction\Contracts\CustomEventInterface;
use Comhon\CustomAction\Models\ActionLocalizedSettings;
use Comhon\CustomAction\Models\ActionScopedSettings;
use Comhon\CustomAction\Models\CustomActionSettings;
use Comhon\CustomAction\Models\CustomEventListener;
use Comhon\CustomAction\Resolver\ModelResolverContainer;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class CustomActionServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-custom-action')
            ->hasConfigFile()
            ->hasMigration('create_laravel-custom-action_table')
            ->hasTranslations()
            ->hasRoute('routes')
            ->hasCommand(GenerateActionCommand::class);
    }

    public function packageRegistered()
    {
        $this->app->singleton(ModelResolverContainer::class, function (Application $app) {
            $resolverClass = $app['config']['custom-action.model_resolver'];

            return new ModelResolverContainer($app->make($resolverClass));
        });
    }

    public function packageBooted()
    {
        Event::listen(
            CustomEventInterface::class,
            [CustomEventHandler::class, 'handle']
        );
        if (config('custom-action.use_policies')) {
            Gate::policy(ActionLocalizedSettings::class, 'App\Policies\CustomAction\ActionLocalizedSettingsPolicy');
            Gate::policy(ActionScopedSettings::class, 'App\Policies\CustomAction\ActionScopedSettingsPolicy');
            Gate::policy(CustomActionInterface::class, 'App\Policies\CustomAction\CustomActionPolicy');
            Gate::policy(CustomActionSettings::class, 'App\Policies\CustomAction\CustomActionSettingsPolicy');
            Gate::policy(CustomEventInterface::class, 'App\Policies\CustomAction\CustomEventPolicy');
            Gate::policy(CustomEventListener::class, 'App\Policies\CustomAction\CustomEventListenerPolicy');
        }
    }
}
