<?php

namespace Comhon\CustomAction;

use Comhon\CustomAction\Commands\GenerateActionCommand;
use Comhon\CustomAction\Resolver\ModelResolverContainer;
use Illuminate\Contracts\Foundation\Application;
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
        try {
            $this->app->get(CustomActionRegistrar::class)->subscribeListeners();
        } catch (\Illuminate\Database\QueryException $e) {
            // do nothing, QueryException may happen when migrate database from scratch.
        }
    }
}
