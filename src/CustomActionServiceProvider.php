<?php

namespace Comhon\CustomAction;

use Comhon\CustomAction\Commands\GenerateActionCommand;
use Comhon\CustomAction\Contracts\BindingFinderInterface;
use Comhon\CustomAction\Contracts\CustomActionInterface;
use Comhon\CustomAction\Contracts\CustomEventInterface;
use Comhon\CustomAction\Contracts\EmailReceiverInterface;
use Comhon\CustomAction\Facades\CustomActionModelResolver as FacadesCustomActionModelResolver;
use Comhon\CustomAction\Files\StoredFile;
use Comhon\CustomAction\Models\ActionLocalizedSettings;
use Comhon\CustomAction\Models\ActionScopedSettings;
use Comhon\CustomAction\Models\CustomActionSettings;
use Comhon\CustomAction\Models\CustomEventListener;
use Comhon\CustomAction\Resolver\CustomActionModelResolver;
use Comhon\CustomAction\Rules\HtmlTemplate;
use Comhon\CustomAction\Rules\IsInstanceOf;
use Comhon\CustomAction\Rules\ModelReference;
use Comhon\CustomAction\Rules\RuleHelper;
use Comhon\CustomAction\Rules\TextTemplate;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
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
        $this->app->singleton(CustomActionModelResolver::class, function (Application $app) {
            return new CustomActionModelResolver($app->make($app['config']['custom-action.model_resolver']));
        });
        $this->app->singletonIf(BindingFinderInterface::class, function (Application $app) {
            return new BindingFinder();
        });
    }

    public function packageBooted()
    {
        Event::listen(
            CustomEventInterface::class,
            [CustomEventHandler::class, 'handle']
        );
        $this->registerPolicies();
        $this->registerRules();
        $this->bindModels();
    }

    public function registerPolicies()
    {
        if (config('custom-action.use_policies')) {
            $policies = Gate::policies();
            if (! isset($policies[ActionLocalizedSettings::class])) {
                Gate::policy(ActionLocalizedSettings::class, 'App\Policies\CustomAction\ActionLocalizedSettingsPolicy');
            }
            if (! isset($policies[ActionScopedSettings::class])) {
                Gate::policy(ActionScopedSettings::class, 'App\Policies\CustomAction\ActionScopedSettingsPolicy');
            }
            if (! isset($policies[CustomActionInterface::class])) {
                Gate::policy(CustomActionInterface::class, 'App\Policies\CustomAction\CustomActionPolicy');
            }
            if (! isset($policies[CustomActionSettings::class])) {
                Gate::policy(CustomActionSettings::class, 'App\Policies\CustomAction\CustomActionSettingsPolicy');
            }
            if (! isset($policies[CustomEventInterface::class])) {
                Gate::policy(CustomEventInterface::class, 'App\Policies\CustomAction\CustomEventPolicy');
            }
            if (! isset($policies[CustomEventListener::class])) {
                Gate::policy(CustomEventListener::class, 'App\Policies\CustomAction\CustomEventListenerPolicy');
            }
        }
    }

    public function registerRules()
    {
        Validator::extend(RuleHelper::getRuleName('model_reference'), ModelReference::class);
        Validator::extend(RuleHelper::getRuleName('is'), IsInstanceOf::class);
        Validator::extend(RuleHelper::getRuleName('text_template'), TextTemplate::class);
        Validator::extend(RuleHelper::getRuleName('html_template'), HtmlTemplate::class);
    }

    public function bindModels()
    {
        FacadesCustomActionModelResolver::bind('stored-file', StoredFile::class);
        FacadesCustomActionModelResolver::bind('email-receiver', EmailReceiverInterface::class);
        FacadesCustomActionModelResolver::bind('custom-event', CustomEventInterface::class);
    }
}
